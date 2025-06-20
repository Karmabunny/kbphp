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
class XML
{

    /**
     * Parse an XML document from a string.
     *
     * Example:
     * ```
     * XML::parse($xml_source, [
     *    'options' => LIBXML_NOCDATA | LIBXML_NOBLANKS,
     *    'filename' => __DIR__ '/etc.xml',
     *    'schema' => $xsd_source,
     * ]);
     * ```
     *
     * Config:
     * - 'filename' include for prettier errors.
     * - 'options' are an bitwise OR of libxml options.
     * - 'schema' an XSD file for additional validation.
     * - 'encoding' (default: UTF-8).
     * - 'recover' try to parse non-well formed documents.
     * - 'entities' an entity loading callback.
     *
     * @link https://www.php.net/manual/en/libxml.constants.php
     * @link https://www.php.net/manual/en/function.libxml-set-external-entity-loader.php
     *
     * @param string $source
     * @param array $config [filename, options, schema, encoding, recover, entities]
     * @return DOMDocument
     * @throws XMLException
     */
    public static function parse(string $source, array $config = [])
    {
        $doc = self::createDocument($config);

        libxml_use_internal_errors(true);
        libxml_set_external_entity_loader($config['entities'] ?? null);

        $doc->loadXML($source, $config['options']);

        self::collectLibXmlErrors(XMLParseException::class, $doc->documentURI);

        // Conditionally validate it.
        if ($schema = $config['schema'] ?? null) {
            self::validate($doc, $schema);
        }

        return $doc;
    }


    /**
     * Parse an XML document from a file.
     *
     * Example:
     * ```
     * XML::parseFile($filename, [
     *    'options' => LIBXML_NOCDATA | LIBXML_NOBLANKS,
     *    'schema_filename' => $xsd_source,
     * ]);
     * ```
     *
     * Config:
     * - 'options' are an bitwise OR of libxml options.
     * - 'schema' an XSD file for additional validation.
     * - 'encoding' (default: UTF-8).
     * - 'recover' try to parse non-well formed documents.
     * - 'entities' an entity loading callback.
     *
     * @link https://www.php.net/manual/en/libxml.constants.php
     * @link https://www.php.net/manual/en/function.libxml-set-external-entity-loader.php
     *
     * @param string $filename
     * @param array $config [options, schema, encoding, recover, entities]
     * @return DOMDocument
     * @throws XMLException
     */
    public static function parseFile(string $filename, array $config = [])
    {
        $config['filename'] = $filename;
        $doc = self::createDocument($config);

        libxml_use_internal_errors(true);
        libxml_set_external_entity_loader($config['entities'] ?? null);

        $doc->load($filename, $config['options']);

        self::collectLibXmlErrors(XMLParseException::class, $doc->documentURI);

        // Conditionally validate it.
        if ($schema = $config['schema'] ?? null) {
            self::validate($doc, $schema);
        }

        return $doc;
    }


    /**
     * Validate the document against a schema.
     *
     * @param DOMDocument $doc
     * @param string $source
     * @return void
     * @throws XMLException
     */
    public static function validate(DOMDocument $doc, string $source)
    {
        libxml_use_internal_errors(true);
        @$doc->schemaValidateSource($source);
        self::collectLibXmlErrors(XMLSchemaException::class, $doc->documentURI);
    }


    /**
     * Create a configured document ready for parsing.
     *
     * @param array $config
     * @return DOMDocument
     */
    private static function createDocument(array &$config)
    {
        // Automatic entity loading isn't a thing anymore. Although we still
        // loading for loading schemas and such. Use the 'entities' callback
        // and built-in loaders for this, or build you own.
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader(true);
        }

        if (!isset($config['options'])) $config['options'] = 0;
        if (!isset($config['filename'])) $config['filename'] = '<anonymous>';
        if (!isset($config['encoding'])) $config['encoding'] = 'UTF-8';

        $doc = new DOMDocument('1.0', $config['encoding']);
        $doc->documentURI = $config['filename'];

        if (isset($config['recover'])) {
            $doc->recover = true;
        }

        return $doc;
    }


    /**
     * Convert libxml errors into an exception.
     *
     * @param string $class _must_ be a XMLParseException type
     * @param string|null $filename
     * @return void
     * @throws XMLException
     */
    private static function collectLibXmlErrors(string $class, $filename)
    {
        $errors = libxml_get_errors();
        if (empty($errors)) return;

        // Get the last 'fatal' or 'error' type error on the stack.
        // Ignore warnings.
        foreach ($errors as $error) {
            if ($error->level === LIBXML_ERR_FATAL) break;
            if ($error->level === LIBXML_ERR_ERROR) break;
            unset($error);
        }

        self::cleanLibXml();

        if (isset($error)) {
            if ($filename) $error->file = $filename;
            $error = new $class($error);
            $error->errors = $errors;
            throw $error;
        }
    }


    /**
     * Tidy up after ourselves.
     *
     * @return void
     */
    private static function cleanLibXml()
    {
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        libxml_set_external_entity_loader(function() { return null; });
    }


    /**
     * Create an entity loader that only loads from within a white list.
     *
     * E.g.
     *
     * ```
     * XML::parse($blob, [
     *   'entities' => XML::allowedEntities([
     *     'http://www.w3.org/2001/xml.xsd',
     *     'http://www.w3.org/2003/05/soap-envelope/',
     *   ]),
     * ]);
     * ```
     *
     * @param array $entities
     * @return callable (public_id, system_id, context) => resource|null
     */
    public static function allowedEntities(array $entities)
    {
        return function ($public_id, $system_id, $context)
            use ($entities)
        {
            if (!in_array($system_id, $entities)) return null;
            return @fopen($system_id, 'r') ?: null;
        };
    }


    /**
     * Create an entity loader that only loads from a set of prefixes.
     *
     * E.g.
     *
     * ```
     * XML::parse($blob, [
     *   'entities' => XML::prefixEntities([
     *     'http://www.w3.org',
     *     'http://www.w3.org',
     *   ]),
     * ]);
     * ```
     *
     * @param array $prefixes
     * @return callable (public_id, system_id, context) => resource|null
     */
    public static function prefixEntities(array $prefixes)
    {
        return function ($public_id, $system_id, $context)
            use ($prefixes)
        {
            foreach ($prefixes as $prefix) {
                if (strpos($system_id, $prefix) === 0) {
                    return @fopen($system_id, 'r') ?: null;
                }
            }
            return null;
        };
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
    public static function processConditionals(DOMNode $node, array $conditions)
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
                $endtag = false;

                $element = $condition->nextSibling;

                // Now find an endif tag.
                while ($element = $element->nextSibling) {
                    if (
                        $element instanceof DOMProcessingInstruction
                        and $element->target === 'endif'
                    ) {
                        $endtag = true;
                        break;
                    }

                    $body[] = $element;
                }

                // Not found, complain real hard.
                if (!$endtag) {
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
        // @phpstan-ignore-next-line : current() can indeed be null.
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
        if (empty($template)) return new DOMDocument();

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
     * @param DOMNode $node
     * @param string $query
     * @param string $type string|bool|int|float|element|list|nodes
     * @return string|bool|int|float|DOMElement|DOMNode[]|Generator<DOMNode>|null
     */
    public static function xpath(DOMNode $node, string $query, string $type = 'nodes')
    {
        // If 'ownerDocument' is null, then the node _is_ the document.
        /** @var DOMDocument $document */
        $document = $node->ownerDocument ?? $node;
        if ($node === $document) $node = null;

        $path = new DOMXPath($document);

        // Sometimes there's a namespace.
        if ($node and $node->namespaceURI) {
            $query = preg_replace('/\/([^\/@]+)/', "/ns:$1", $query);
            $path->registerNamespace('ns', $node->namespaceURI);
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
                return self::getElementIterator($results)->current();

            case 'list':
                if (!$results) return [];
                return iterator_to_array(self::getElementIterator($results));

            case 'nodes':
            default:
                if (!$results) return null;
                return self::getNodeIterator($results);
        }
    }


    /**
     * Get an iterator for a DOMNodeList.
     *
     * The builtin `getIterator()` was only introduced with php8.
     *
     * @param DOMNodeList $list
     * @return Generator<DOMNode>
     */
    private static function getNodeIterator(DOMNodeList $list)
    {
        for ($i = 0; $i < $list->length; $i++) {
            $item = $list->item($i);
            yield $i => $item;
        }
    }


    /**
     * Get an iterator for elements in a DOMNodeList.
     *
     * The builtin `getIterator()` was only introduced with php8.
     *
     * @param DOMNodeList $list
     * @return Generator<DOMElement>
     */
    private static function getElementIterator(DOMNodeList $list)
    {
        foreach (self::getNodeIterator($list) as $i => $item) {
            if (!($item instanceof DOMElement)) continue;
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
        // Get the root element of a document first.
        if ($parent instanceof DOMDocument) {
            $parent = $parent->firstChild;
        }

        foreach (self::getElementIterator($parent->childNodes) as $element) {

            if ($element->nodeName === $tag_name) return $element;
        }

        return null;
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

        foreach (self::getElementIterator($parent->childNodes) as $element) {
            /** @var DOMElement $element */

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
     * Get a list of strings from the target child element.
     *
     * @param DOMElement $node
     * @param string $tag
     * @return string[]
     */
    public static function getChildrenText(DOMElement $node, string $tag): array
    {
        /** @var DOMElement[] $items */
        $items = self::xpath($node, './' . $tag, 'list');
        return array_map([XML::class, 'text'], $items);
    }


    /**
     * Expect one of these nodes in the children.
     *
     * @param DOMNode $parent
     * @param string[] $wanted
     * @return DOMElement
     * @throws XMLAssertException If no tag found
     */
    public static function expectOneOf(DOMNode $parent, array $wanted)
    {
        foreach (self::getElementIterator($parent->childNodes) as $element) {
            /** @var DOMElement $element */
            if (!in_array($element->nodeName, $wanted)) continue;
            return $element;
        }

        $tags = implode(', ', array_keys($wanted));
        throw new XMLAssertException('Missing element, one of: ' . $tags);
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
     * @return string|null
     */
    public static function attr($element, string $name)
    {
        if ($element instanceof DOMDocument) {
            $element = $element->documentElement;
        }

        if (!$element->hasAttribute($name)) {
            return null;
        }

        return trim($element->getAttribute($name));
    }


    /**
     * Get the string of a node.
     * @param DOMNode $node
     * @return string
     */
    public static function toString(DOMNode $node): string
    {
        /** @var DOMDocument */
        $document = $node->ownerDocument ?? $node;
        return $document->saveXML($node);
    }


    /**
     * Print a node to the standard output.
     *
     * @param DOMNode $node
     * @return void echos
     */
    public static function print(DOMNode $node)
    {
        /** @var DOMDocument */
        $document = $node->ownerDocument ?? $node;

        if ($node === $document) {
            $document->save('php://output');
        }
        else {
            echo $document->saveXML($node);
        }
    }
}
