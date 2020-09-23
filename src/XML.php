<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use SimpleXMLElement;

/**
 * XML helper methods.
 *
 * @package karmabunny\kb
 */
abstract class XML {

    /**
     * Interpolate and escape XML strings.
     *
     * Specify {{etc}} or {{0}} in the template and provide the same
     * in the $args array. Values will be appropriately escaped.
     *
     * Use <?condition></?> to create conditional sections.
     *
     * Or <?conditional attr="hi"/> for a single element.
     *
     * @param string $template
     * @param array $args
     * @return string
     */
    public static function format(string $template, array $args): string
    {
        if (empty($template)) return '';

        $replace = [];
        $subjects = [];

        foreach ($args as $key => $value) {
            $subjects[] = '{{' . $key . '}}';
            $replace[] = htmlspecialchars($value);
        }

        $xml = str_replace($subjects, $replace, $template);

        if (strpos($xml, '<?') !== false) {
            // Match any patterns that look like: "<?condition>body</?\>".
            // Strip anything with a falsey condition arg.
            // Replace it with just the body otherwise.
            $xml = preg_replace_callback(
                '/<\?([^>]+)>(.+?)<\/\?>/ms',
                function ($matches) use ($args) {
                    list($_, $condition, $body) = $matches;
                    return $args[$condition] ? $body : '';
            }, $xml);

            // Match singular conditional elements.
            $xml = preg_replace_callback(
                '/<\?(([^\s]+)[^>]*)\/>/ms',
                function ($matches) use ($args) {
                    list($_, $body, $condition) = $matches;
                    return $args[$condition] ? "<{$body}/>" : '';
            }, $xml);
        }

        return $xml;
    }


    /**
     * Get a single value from an element and cast it correctly.
     *
     * @param SimpleXMLElement $xml
     * @param string $path
     * @param string $type
     * @param mixed|null $not_found
     * @return mixed
     */
    public static function xpath(SimpleXMLElement $xml, string $path, string $type = 'string', $not_found = null)
    {
        $element = $xml->xpath($path);

        if (empty($element)) {
            // Use the provided fallback.
            if ($not_found !== null) {
                return $not_found;
            }

            // Fallbacks if the fallback doesn't exist.
            switch ($type) {
                case 'string':
                    return '';

                case 'bool':
                    return false;

                case 'int':
                case 'float':
                    return 0;
            }

            return null;
        }

        /** @var SimpleXMLElement|null */
        $element = isset($element[0]) ? $element[0] : null;

        switch ($type) {
            default:
            case 'string':
                return (string) $element;

            case 'int':
                return (int) $element;

            case 'float':
                return (double) $element;

            case 'bool':
                return self::parseBoolean($element);
        }
    }


    public static function enum(SimpleXMLElement $xml, string $path, array $params)
    {
        $value = self::xpath($xml, $path, 'int', 0);

        if (!isset($params[$value])) {
            return next($params);
        }

        return $params[$value];
    }


    /**
     * XML booleans are... unclear. This is an attempt.
     *
     * @param SimpleXMLElement|null $thing
     * @return bool true/false and nothing else
     */
    private static function parseBoolean(?SimpleXMLElement $thing = null)
    {
        // No element.
        if ($thing === null) {
            return false;
        }

        // It's a string? ooh.
        switch (strtolower(trim($thing))) {
            case 'true':
            case 'yes':
            case 'good':
            case 'okay':
            case 't':
            case 'y':
                return true;

            case 'false':
            case 'no':
            case 'bad':
            case 'null':
            case 'f':
            case 'n':
            case 'undefined': // Javascript
            case 'none': // Python
            case '\n': // Postgres
                return false;
        }

        // An empty element like: <This/> is true.
        if ((string) $thing === '') {
            return true;
        }

        // Perhaps it's numerical.
        if ($thing == 0) {
            return false;
        }

        // Well it's SOMETHING.
        return true;
    }
}
