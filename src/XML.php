<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use DOMDocument;
use SimpleXMLElement;
use Throwable;

/**
 * XML helper methods.
 *
 * @package karmabunny\kb
 */
abstract class XML {

    /**
     * Parse an XML document.
     *
     * This will emit appropriate exception when encountering parsing errors.
     *
     * @param string $source
     * @param int $options libxml options
     * @return SimpleXMLElement
     * @throws XMLParseException
     */
    public static function parse(string $source, int $options = 0)
    {
        // I honestly don't care about anyone trying to load entities.
        // It's unsafe and in PHP8+ it's permanently disabled.
        // So if they _really_ want it, they can enable it.
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader(true);
        }

        $doc = new DOMDocument();

        try {
            $doc->loadXML($source, $options);
        }
        catch (Throwable $error) {
            throw new XMLParseException($error->getMessage());
        }

        return simplexml_import_dom($doc);
    }


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


    /**
     * Convert an integer into something more useful, with a map.
     *
     * The first element of the map will be the fallback if the value isn't
     * found or the map can't find an element.
     *
     * Example:
     * ```
     *   XML::enum($dom, '//path/to/number', [
     *      0 => 'null',
     *      1 => 'yes',
     *      2 => 'no',
     *   ]);
     *   // => outputs one of null/yes/no
     *   // => unknown/no-value is 'null'
     * ```
     *
     * @param SimpleXMLElement $xml
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public static function enum(SimpleXMLElement $xml, string $path, array $params)
    {
        $value = self::xpath($xml, $path, 'int', 0);
        $value = $params[$value] ?? null;

        if ($value === null) {
            return Arrays::first($params);
        }

        return $value;
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
                return false;
        }

        // Postgres + MySQL
        if (trim($thing) === '\N') {
            return false;
        }

        // An empty element like: <This/> is true.
        if ((string) $thing === '') {
            return true;
        }

        // Perhaps it's numerical.
        if (preg_match('/^[+\-\.0-9]+$/', trim($thing)) and $thing == 0) {
            return false;
        }

        // Well it's SOMETHING.
        return true;
    }


    /**
     * Requires that at least one element with the given tag exist and returns
     * the first found.
     *
     * @param SimpleXMLElement $parent
     * @param string $tag_name
     * @return SimpleXMLElement
     * @throws XMLAssertException If there were no nodes with that tag
     */
    public static function expectFirst(SimpleXMLElement $parent, string $tag_name)
    {
        $element = self::first($parent, $tag_name);

        if ($element === null) {
            throw new XMLAssertException("Missing element required '{$tag_name}''");
        }

        return $element;
    }


    /**
     * Requires that at least one element with the given tag exist, and
     * returns the text content of the first found.
     *
     * @param SimpleXMLElement $parent
     * @param string $tag_name
     * @return string
     * @throws XMLAssertException If there were no nodes with that tag name
     */
    public static function expectFirstText(SimpleXMLElement $parent, string $tag_name)
    {
        return self::text(self::expectFirst($parent, $tag_name));
    }


    /**
     * Fetches the first element of a given tag name or null if none are found.
     *
     * @param SimpleXMLElement $parent
     * @param string $tag_name
     * @return SimpleXMLElement|null
     */
    public static function first(SimpleXMLElement $parent, string $tag_name)
    {
        $element = $parent->xpath('./' . $tag_name)[0] ?? null;
        if ($element === null) return null;
        return $element;
    }


    /**
     * Fetches the text of the first element of a given tag name, or null if
     * the element wasn't found.
     *
     * @param SimpleXMLElement $parent
     * @param string $tag_name
     * @return string|null
     */
    public static function firstText(SimpleXMLElement $parent, string $tag_name)
    {
        $element = self::first($parent, $tag_name);
        if ($element === null) return null;

        return self::text($element);
    }


    /**
     * Iterates over the children (1 level deep) of a parent and fetches the
     * first of all wanted elements by tag name.
     *
     * Note, if there are two of the same tag name, it will only get the first.
     *
     * Output is indexed by the relevant tag name.
     *
     * @param SimpleXMLElement $parent
     * @param string[] $wanted
     * @return SimpleXMLElement An array of all the wanted children
     * @throws XMLAssertException If not all wanted tags are found
     */
    public static function gatherChildren(SimpleXMLElement $parent, array $wanted)
    {
        $wanted = array_fill_keys($wanted, true);
        $fetched = [];

        foreach ($parent->children() as $element) {
            if (!$element) continue;

            $name = $element->getName();
            if (!array_key_exists($name, $wanted)) continue;

            $fetched[$name] = $element;
            unset($wanted[$name]);
        }

        if (!empty($wanted)) {
            $tags = implode(', ', array_keys($wanted));
            throw new XMLAssertException('Missing wanted tags: ' . $tags);
        }

        return $fetched;
    }


    /**
     * Fetches the text content of a node and trims it.
     *
     * @param SimpleXMLElement $node
     * @return string
     */
    public static function text(SimpleXMLElement $node)
    {
        return trim((string) $node);
    }


    /**
     * Fetches an attribute from an element and trims it.
     *
     * @param SimpleXMLElement $elem
     * @param string $name
     * @return string
     */
    public static function attr(SimpleXMLElement $elem, string $name)
    {
        return trim($elem->attributes()[$name] ?? '');
    }
}
