<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

namespace karmabunny\kb;

use Error;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;

/**
 * This is a helper class for working with attributes. It's designed to aid
 * extraction of attributes and tags. This also provides a sort of
 * compatibility between attributes and doc tags.
 *
 * Collect tags from a target class with the `parse($target)` method. This will
 * pick up any attributes and doc tags, according to their respective filters.
 *
 * Attributes are defined naturally, using the `#[]` syntax in PHP 8+.
 *
 * For doc tags define the `@attribute <name> [<target> ...]` tag in the class
 * doc of the attribute class. The `<target>` is an optional, one or many, that
 * controls which aspects the attribute can be applied to.
 *
 * Target types:
 *  - class
 *  - function
 *  - method
 *  - property
 *  - constant
 *
 * Note, this list mirrors the `Attribute::TARGET` flags. However, parameters
 * are not included because they don't have doc comments.
 *
 * For example:
 * - `@attribute tag1` - match any target with `@tag1`.
 * - `@attribute tag2 property|method` - match only properties and methods with `@tag2`.
 * - `@attribute tag2 method` - match only methods with `@tag3`.
 *
 * Note, doc tags are case sensitive.
 *
 * @package karmabunny\kb
 */
abstract class AttributeTag
{

    /** @var Reflector|null */
    public $reflect;


    /**
     * Build this tag.
     *
     * @param mixed $args
     */
    public function __construct(...$args) {}


    /**
     * Default parser for doc tag arguments.
     *
     * Given a tag like:
     * `> @tag 1, 2, "three"`
     *
     * The attribute is built with:
     * `new Tag(1, 2, 'three');`
     *
     * Parameters are parsed as JSON. Single quotes will not work and appear
     * as `null`.
     *
     * @param string $args
     * @return static|null
     */
    protected static function build(string $args)
    {
        if ($args) {
            $args = json_decode("[{$args}]");
        }
        else {
            $args = [];
        }

        return new static(...$args);
    }


    /**
     * Parse all attributes of an object or class and it's sub reflections.
     *
     * @param string|object $target
     * @return static[]
     * @throws Error
     */
    public static function parse($target): array
    {
        $reflect = new ReflectionClass($target);

        $tags = [];

        $more = self::parseReflector($reflect);
        array_push($tags, ...$more);

        foreach ($reflect->getMethods() as $method) {
            $more = self::parseReflector($method);
            array_push($tags, ...$more);
        }

        foreach ($reflect->getProperties() as $property) {
            $more = self::parseReflector($property);
            array_push($tags, ...$more);
        }

        foreach ($reflect->getReflectionConstants() as $constant) {
            $more = self::parseReflector($constant);
            array_push($tags, ...$more);
        }

        return $tags;
    }


    /**
     * Parse all and tags attributes of a standalone function.
     *
     * @param callable $function
     * @return static[]
     * @throws Error
     */
    public static function parseFunction($function): array
    {
        $reflect = new ReflectionFunction($function);
        return self::parseReflector($reflect);
    }


    /**
     * Parse attributes and tags of a reflection object.
     *
     * @param ReflectionClass|ReflectionFunction|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $reflect
     * @return static[]
     * @throws Error when the reflector cannot parse attributes or tags
     */
    public static function parseReflector($reflect): array
    {
        // Static store for class metadata.
        // This only needs to be parsed once.
        static $meta = [];

        static $MAP = [
            'class' => ReflectionClass::class,
            'function' => ReflectionFunction::class,
            'method' => ReflectionMethod::class,
            'property' => ReflectionProperty::class,
            'constant' => ReflectionClassConstant::class,
        ];

        // A bit of inception here. We're parsing the attributes of the
        // attribute class itself. With this we can define the rules for
        // parsing attributes.
        if (!$meta) {
            $self = new ReflectionClass(static::class);

            $doc = $self->getDocComment() ?: '';
            $doc = Reflect::getDocTag($doc, 'attribute');
            $doc = reset($doc);

            [$name, $args] = explode(' ', $doc, 2) + ['', ''];
            $args = explode('|', $args);

            $meta['name'] = $name;
            $meta['filter'] = [];

            foreach ($args as $arg) {
                $meta['filter'][] = trim($arg);
            }
        }

        $tags = [];

        // Search for natural attributes.
        if (PHP_VERSION_ID >= 80000) {

            if (!method_exists($reflect, 'getAttributes')) {
                $type = is_object($reflect) ? get_class($reflect) : (string) $reflect;
                throw new Error('Cannot parse attributes from: ' . $type);
            }

            // We're looking for instances of ourself (the attribute) and any
            // extensions of us.
            $attributes = $reflect->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($attributes as $attribute) {
                $tag = $attribute->newInstance();
                $tag->reflect = $reflect;
                $tags[] = $tag;
            }
        }

        // Only parse doc tags if enabled.
        if ($meta['name']) {

            if (!method_exists($reflect, 'getDocComment')) {
                $type = is_object($reflect) ? get_class($reflect) : (string) $reflect;
                throw new Error('Cannot parse doc comments from: ' . $type);
            }

            $doc = $reflect->getDocComment() ?: '';
            $docs = Reflect::getDocTag($doc, $meta['name']);

            if ($docs) {
                // Check if we're allowed to parse this target type.
                foreach ($MAP as $name => $class) {
                    if (
                        ($reflect instanceof $class)
                        and !in_array($name, $meta['filter'])
                    ) {
                        $filter = implode(', ', $meta['filter']);
                        throw new Error("Tag \"@{$meta['name']}\" cannot target {$name} (allowed targets: {$filter})");
                    }
                }

                // The builder can be redefined for each concrete attribute.
                foreach ($docs as $doc) {
                    $tag = static::build($doc);
                    if (!$tag) continue;

                    $tag->reflect = $reflect;
                    $tags[] = $tag;
                }
            }
        }

        return $tags;
    }
}
