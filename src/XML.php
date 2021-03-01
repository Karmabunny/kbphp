<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMProcessingInstruction;
use DOMXPath;
use Generator;

// Just to be sure.
if (PHP_VERSION_ID < 80000) {
    libxml_disable_entity_loader(true);
}

/**
 * XML helper methods.
 *
 * @package karmabunny\kb
 */
abstract class XML {

    /**
     * Parse an XML document.
     *
     * Example:
     * ```
     * XML::parse($xml_source, [
     *    'options' => LIBXML_NOCDATA | LIBXML_NOBLANKS,
     *    'filename' => __DIR__ '/etc.xml',
     *    'validate' => $xsd_source,
     * ]);
     * ```
     * Config:
     * - 'filename' include for prettier errors.
     * - 'options' are an bitwise OR of libxml options.
     * - 'validate' an XSD file for additional validation.
     * - 'encoding'
     *
     * @link https://www.php.net/manual/en/libxml.constants.php
     *
     * @param string $source
     * @param array $config
     * @return DOMDocument
     * @throws XMLException
     */
    public static function parse(string $source, array $config = [])
    {

        // I honestly don't care about anyone trying to load entities.
        // It's unsafe and in PHP8+ it's permanently disabled.
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader(true);
        }

        if (!isset($config['options'])) $config['options'] = 0;
        if (!isset($config['filename'])) $config['filename'] = '<anonymous>';
        if (!isset($config['encoding'])) $config['encoding'] = 'UTF-8';

        $doc = new DOMDocument('1.0', $config['encoding']);
        $doc->documentURI = $config['filename'];

        libxml_use_internal_errors(true);
        $doc->loadXML($source, $config['options']);
        self::collectLibXmlErrors(XMLParseException::class, $doc->documentURI);

        // Conditionally validate it.
        if ($validate = $config['validate'] ?? null) {
            libxml_use_internal_errors(true);
            $doc->schemaValidateSource($validate);
            self::collectLibXmlErrors(XMLSchemaException::class, $doc->documentURI);
        }

        return $doc;
    }


    /**
     * Convert libxml errors into an exception.
     *
     * @param string $type
     * @param string|null $filename
     * @return void
     * @throws XMLException
     */
    private static function collectLibXmlErrors(string $type, $filename)
    {
        $errors = libxml_get_errors();
        if (empty($errors)) return;

        // Get the last 'fatal' error on the stack.
        foreach ($errors as $error) {
            switch ($error->level) {
                case LIBXML_ERR_FATAL: break;
                default: unset($error);
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (isset($error)) {
            if ($filename) $error->file = $filename;
            throw new $type($error);
        }
    }


    /**
     * Process if/end PI tags.
     *
     * Given the matching condition, pass or remove the conditional PI tags.
     *
     * This will modify the $node parameter.
     *
     * @example
     * ```xml
     * <!-- Conditional blocks -->
     * <?if conditional ?>
     *    <item>
     *       <block attr="value"/>
     *    </item>
     * <?endif ?>
     *
     * <!-- Conditional element -->
     * <?if element attr="value" ?>
     * ```
     *
     * @param DOMNode $node
     * @param array $conditions
     * @return void
     * @throws XMLException
     */
    public static function processConditionals(DOMNode &$node, array $conditions)
    {
        /** @var DOMDocument */
        $document = $node->ownerDocument ?? $node;

        // Fid all the 'if' PI tags.
        $conditionals = self::xpath($node, '//processing-instruction("if")', 'nodes');

        foreach ($conditionals as $condition) {
            if (!($condition instanceof DOMProcessingInstruction)) continue;
            if (!$condition->parentNode) continue;
            if (!$condition->data) continue;

            // Parse out the condition name.
            $matches = [];
            if (!preg_match('/ *([^ ]+) */', $condition->data, $matches)) {
                throw new XMLException('Invalid condition: ' . $condition->data);
            };

            $name = $matches[1];

            // Has attributes, it's an inline.
            // regex: (1:name)="(3:value)"
            if (preg_match_all('/([^= >]+) *= *([\'"])([^\'"]*)\2/', $condition->data, $matches, PREG_SET_ORDER)) {

                // The condition is false, so remove this element and move on.
                if (empty($conditions[$name])) {
                    $condition->parentNode->removeChild($condition);
                    continue;
                }

                // Otherwise, replace it with a real element.
                $element = $document->createElement($name);

                foreach ($matches as $match) {
                    $name = $match[1];
                    $value = $match[3];
                    $element->setAttribute($name, $value);
                }

                $condition->parentNode->replaceChild($element, $condition);
            }

            // No attributes, it's a block.
            else {
                $body = [];

                /** @var DOMProcessingInstruction|null */
                $element = $condition->nextSibling;

                // Now find an endif tag.
                while (
                    ($element = $element->nextSibling) and
                    ((!($element instanceof DOMProcessingInstruction) or
                    $element->target !== 'endif'))
                ) {
                    $body[] = $element;
                }

                // Not found, complain real hard.
                if (
                    !$element or
                    !($element instanceof DOMProcessingInstruction) or
                    $element->target !== 'endif'
                ) {
                    throw new XMLException('Cannot find endif for condition, line ' . $condition->getLineNo());
                }

                // The conditional is false, so let's remove what's in-between.
                if (empty($conditions[$name])) {
                    foreach ($body as $item) {
                        $condition->parentNode->removeChild($item);
                    }
                }

                // Also remove the IP tags.
                $condition->parentNode->removeChild($element);
                $condition->parentNode->removeChild($condition);
            }
        }

        // Find any endifs that weren't processed.
        $conditionals = self::xpath($node, '//processing-instruction("endif")', 'nodes');
        if ($first = $conditionals->current()) {
            throw new XMLException('Unexpected dangling endif on line: ' . $first->getLineNo());
        }
    }


    /**
     * Interpolate and escape XML strings.
     *
     * This creates an XML object.
     *
     * Specify {{etc}} or {{0}} in the template and provide the same
     * in the $args array. Values will be appropriately escaped.
     *
     * Perform conditional with the `<?if ?>` PI tag.
     * ```xml
     * <!-- Conditional blocks -->
     * <?if conditional ?>
     *    <item>
     *       <block attr="value"/>
     *    </item>
     * <?endif ?>
     *
     * <!-- Conditional element -->
     * <?if element attr="value" ?>
     * ```
     *
     * @param string $template
     * @param array $args
     * @return DOMDocument
     */
    public static function format(string $template, array $args): DOMDocument
    {
        if (empty($template)) return '';

        $replace = [];
        $subjects = [];

        foreach ($args as $key => $value) {
            $subjects[] = '{{' . $key . '}}';
            $replace[] = htmlspecialchars($value);
        }

        $xml = str_replace($subjects, $replace, $template);

        $doc = self::parse($xml);
        self::processConditionals($doc, $args);
        return $doc;
    }


    /**
     * Perform an XPath query on the node.
     *
     * By default, this will return an iterator of `DOMNode`. You can ask this
     * method to convert it to something more useful.
     *
     * Types:
     * - string - via `::text()`
     * - bool - via `::boolean()`
     * - int
     * - float
     * - element - a `DOMElement` or `null`
     * - list - `DOMElement[]`
     * - nodes - `Generator<DOMNode>`
     *
     * @param DOMNode $xml
     * @param string $path
     * @param string $type string|bool|int|float|element|list|nodes
     * @return string|bool|int|float|DOMElement|DOMNode[]|Generator<DOMNode>
     */
    public static function xpath(DOMNode $node, string $query, string $type = 'nodes')
    {
        // Get the document.
        $document = $node->ownerDocument ?? $node;
        if ($node === $document) $node = null;

        $path = new DOMXPath($document);

        // Sometimes there's a namespace.
        if ($ns = $document->namespaceURI) {
            $query = preg_replace('/\\[^:]+/', '\\' . $ns, $query);
        }

        // Do the search.
        $results = $path->query($query, $node);

        switch ($type) {
            case 'string':
                if (empty($results[0])) return '';
                return self::text($results[0]);

            case 'int':
                if (empty($results[0])) return 0;
                return (int) self::text($results[0]);

            case 'float':
                if (empty($results[0])) return 0.0;
                return (float) self::text($results[0]);

            case 'bool':
                if (empty($results[0])) return false;
                return self::boolean($results[0]);

            case 'element':
                if (!$results) return null;
                return self::getNodeIterator($results, true)->current();

            case 'list':
                if (!$results) return [];
                return iterator_to_array(self::getNodeIterator($results, true));

            case 'nodes':
            default:
                if (!$results) return null;
                return self::getNodeIterator($results, false);
        }
    }


    /**
     * Get an iterator for a DOMNodeList. With optional element filtering.
     *
     * The builtin `getIterator()` was only introduced with php8.
     *
     * @param DOMNodeList $list
     * @param bool $elements_only
     * @return Generator<DOMNode>
     */
    private static function getNodeIterator(DOMNodeList $list, bool $elements_only)
    {
        for ($i = 0; $i < $list->length; $i++) {
            $item = $list->item($i);
            if ($elements_only and !($item instanceof DOMElement)) continue;

            yield $i => $item;
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
     * @param DOMNode $xml
     * @param string $path
     * @param array $params
     * @return mixed
     */
    public static function enum(DOMNode $xml, string $path, array $params)
    {
        $value = self::xpath($xml, $path, 'int') ?: 0;
        $value = $params[$value] ?? null;

        if ($value === null) {
            return Arrays::first($params);
        }

        return $value;
    }


    /**
     * XML booleans are... unclear. This is an attempt.
     *
     * @param DOMNode $thing
     * @return bool true/false and nothing else
     */
    public static function boolean(DOMNode $thing)
    {
        // No element.
        $thing = self::text($thing);

        // It's a string? ooh.
        switch (strtolower($thing)) {
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
        if ($thing === '\N') {
            return false;
        }

        // An empty element like: <This/> is true.
        if ($thing === '') {
            return true;
        }

        // Perhaps it's numerical.
        if (preg_match('/^[+\-\.0-9]+$/', $thing) and $thing == 0) {
            return false;
        }

        // Well it's SOMETHING.
        return true;
    }


    /**
     * Requires that at least one element with the given tag exist and returns
     * the first found.
     *
     * @param DOMNode $parent
     * @param string $tag_name
     * @return DOMElement
     * @throws XMLAssertException If there were no nodes with that tag
     */
    public static function expectFirst(DOMNode $parent, string $tag_name)
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
     * @param DOMNode $parent
     * @param string $tag_name
     * @return string
     * @throws XMLAssertException If there were no nodes with that tag name
     */
    public static function expectFirstText(DOMNode $parent, string $tag_name)
    {
        return self::text(self::expectFirst($parent, $tag_name));
    }


    /**
     * Fetches the first element of a given tag name or null if none are found.
     *
     * @param DOMNode $parent
     * @param string $tag_name
     * @return DOMElement|null
     */
    public static function first(DOMNode $parent, string $tag_name)
    {
        /** @var DOMElement */
        $element = self::xpath($parent, './' . $tag_name, 'element');
        if ($element === null) return null;
        return $element;
    }


    /**
     * Fetches the text of the first element of a given tag name, or null if
     * the element wasn't found.
     *
     * @param DOMNode $parent
     * @param string $tag_name
     * @return string|null
     */
    public static function firstText(DOMNode $parent, string $tag_name)
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
     * @param DOMNode $parent
     * @param string[] $wanted
     * @return DOMElement[] [name => element]
     * @throws XMLAssertException If not all wanted tags are found
     */
    public static function gatherChildren(DOMNode $parent, array $wanted)
    {
        $wanted = array_fill_keys($wanted, true);
        $fetched = [];

        foreach (self::getNodeIterator($parent->childNodes, true) as $element) {
            $name = $element->nodeName;
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
     * @param DOMNode $node
     * @return string
     */
    public static function text(DOMNode $node)
    {
        return trim($node->textContent);
    }


    /**
     * Fetches an attribute from an element and trims it.
     *
     * @param DOMDocument|DOMElement $element
     * @param string $name
     * @return string
     */
    public static function attr($element, string $name)
    {
        if ($element instanceof DOMDocument) {
            $element = $element->documentElement;
        }
        return trim($element->getAttribute($name));
    }
}
