<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

namespace karmabunny\kb;

use Error;
use JsonException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use TypeError;

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

    const MODE_ATTRIBUTES = 1;

    const MODE_DOCTAGS = 2;

    const MODE_ALL = 3;


    /**
     * The reflection object that this tag was found on.
     *
     * @var Reflector|null
     */
    public $reflect;


    /**
     * Build this tag.
     */
    public function __construct() {}


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
     * @param string $content
     * @return static|null
     */
    protected static function build(string $content)
    {
        try {
            $args = Json::decode("[{$content}]");
            // @phpstan-ignore-next-line : Don't care.
            return new static(...$args);
        }
        // phpcs:ignore
        catch (JsonException $exception) {
            throw new Error("Error parsing: '{$content}' for tag: " . static::class, 0, $exception);
        }
        catch (TypeError $error) {
            throw new Error("Error parsing '{$content}' for tag: " . static::class, 0, $error);
        }
    }


    /**
     * Parse all attributes of an object or class and it's sub reflections.
     *
     * @param string|object $target
     * @param int $modes
     * @return static[]
     */
    public static function parse($target, int $modes = self::MODE_ALL): array
    {
        $reflect = new ReflectionClass($target);

        $tags = [];

        $more = static::parseReflector($reflect, $modes);
        array_push($tags, ...$more);

        foreach ($reflect->getMethods() as $method) {
            $more = static::parseReflector($method, $modes);
            array_push($tags, ...$more);
        }

        foreach ($reflect->getProperties() as $property) {
            $more = static::parseReflector($property, $modes);
            array_push($tags, ...$more);
        }

        foreach ($reflect->getReflectionConstants() as $constant) {
            $more = static::parseReflector($constant, $modes);
            array_push($tags, ...$more);
        }

        return $tags;
    }


    /**
     * Parse all and tags attributes of a standalone function.
     *
     * @param callable $function
     * @param int $modes
     * @return static[]
     */
    public static function parseFunction($function, int $modes = self::MODE_ALL): array
    {
        $reflect = new ReflectionFunction($function);
        return static::parseReflector($reflect, $modes);
    }


    /**
     * Parse attributes and tags of a reflection object.
     *
     * @param ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $reflect
     * @param int $modes
     * @return static[]
     */
    public static function parseReflector($reflect, int $modes = self::MODE_ALL): array
    {
        $tags = [];

        if (PHP_VERSION_ID < 80000) {
            if ($modes === self::MODE_ATTRIBUTES) {
                throw new Error('Attributes are not supported in this version of PHP');
            }

            $modes ^= self::MODE_ATTRIBUTES;
        }

        if ($modes & self::MODE_ATTRIBUTES) {
            $more = static::parseReflectorAttributes($reflect);
            array_push($tags, ...$more);
        }

        if ($modes & self::MODE_DOCTAGS) {
            $more = static::parseReflectorDocTags($reflect);
            array_push($tags, ...$more);
        }

        return $tags;
    }


    /**
     * Parse attributes (PHP+) of a reflection object.
     *
     * @param ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $reflect
     * @return static[]
     */
    public static function parseReflectorAttributes($reflect): array
    {
        if (PHP_VERSION_ID < 80000) {
            throw new Error('Attributes are not supported in this version of PHP');
        }

        // Safety net only because we haven't got strong types on '$reflect'.
        if (!method_exists($reflect, 'getAttributes')) {
            throw new Error('Cannot parse attributes from: ' . get_class($reflect));
        }

        // We're looking for instances of ourself (the attribute) and any
        // extensions of us.
        // @phpstan-ignore-next-line : PHP8 only.
        $attributes = $reflect->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

        $tags = [];

        foreach ($attributes as $attribute) {
            /** @var static $tag */
            $tag = $attribute->newInstance();
            $tag->reflect = $reflect;
            $tags[] = $tag;
        }

        return $tags;
    }


    /**
     * Parse doc tags of a reflection object.
     *
     * @param ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty|ReflectionClassConstant|ReflectionParameter $reflect
     * @return static[]
     */
    public static function parseReflectorDocTags($reflect): array
    {
        // Static store for class metadata. This only needs to be parsed once.
        // A bit of inception here. We're parsing the @doctags of the tag
        // class itself. With this we can define the rules for @doctags.
        static $_META = [];
        $meta = $_META[static::class] ?? null;

        if ($meta === null) {
            $meta = self::getMetaDocTag();
            $_META[static::class] = $meta;
        }

        // Only parse doc tags if enabled.
        if (!$meta['name']) {
            return [];
        }

        // Safety net only because we haven't got strong types on '$reflect'.
        if (!method_exists($reflect, 'getDocComment')) {
            throw new Error('Cannot parse doc comments from: ' . get_class($reflect));
        }

        $doc = $reflect->getDocComment() ?: '';
        $docs = Reflect::getDocTag($doc, $meta['name']);

        if ($docs) {
            // Check if we're allowed to parse this target type.
            $valid = 0;
            foreach ($meta['filter'] as $class) {
                if ($reflect instanceof $class) {
                    $valid++;
                }
            }

            if (!$valid) {
                $filters = implode(', ', array_keys($meta['filter']));
                $target = strtr(strtolower(get_class($reflect)), [
                    'reflection' => '',
                    'classconstant' => 'constant',
                ]);

                throw new Error("Tag \"@{$meta['name']}\" cannot target {$target} (allowed targets: {$filters})");
            }
        }

        $tags = [];

        // The builder can be redefined for each concrete attribute.
        foreach ($docs as $doc) {
            $tag = static::build($doc);
            if (!$tag) continue;

            $tag->reflect = $reflect;
            $tags[] = $tag;
        }

        return $tags;
    }


    /**
     * Get the doc tag metadata for this class.
     *
     * This 'meta' tag is declared on the class of the 'AttributeTag'.
     * It describes the name of the tag as it would be used elsewhere and the
     * targets that it can be attached to.
     *
     * Example:
     *
     * `@attribute my-tag method|property`
     *
     * Valid targets:
     * - class
     * - function
     * - method
     * - property
     * - constant (this is a class constant)
     *
     * Using this tag:
     *
     * ```
     * // @my-tag arg1, arg2
     * public function myMethod() {}
     * ```
     *
     * @return array [ name, filter ]
     *   - name: tag name
     *   - filter: reflection target, as class names
     */
    public static function getMetaDocTag(): array
    {
        static $MAP = [
            'class' => ReflectionClass::class,
            'function' => ReflectionFunction::class,
            'method' => ReflectionMethod::class,
            'property' => ReflectionProperty::class,
            'constant' => ReflectionClassConstant::class,
        ];

        // 'name' is the tag name to look for.
        // 'filter' is the target types (see $MAP).
        $meta = [
            'name' => null,
            'filter' => [],
        ];

        $self = new ReflectionClass(static::class);

        $doc = $self->getDocComment() ?: '';
        $doc = Reflect::getDocTag($doc, 'attribute');
        $doc = reset($doc);

        if ($doc) {
            list($name, $args) = explode(' ', $doc, 2) + ['', ''];
            $args = explode('|', $args);

            $meta['name'] = $name;
            $meta['filter'] = [];

            foreach ($args as $arg) {
                $arg = trim($arg);
                $filter = $MAP[$arg] ?? null;
                if (!$filter) continue;

                $meta['filter'][$arg] = $filter;
            }
        }

        return $meta;
    }
}
