<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Generator;
use Reflection;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use Traversable;

/**
 * I guess this is useful.
 *
 * @package karmabunny\kb
 */
class Reflect
{

    /** Matching `@thing description` */
    const RE_DOCTYPE = '/@([a-zA-Z]\w+)([^\n\*]+)/';

    /** Matching `@return type description` */
    const RE_RETURN = '/@return\s([a-zA-Z0-9_\|]+)\s*([^\n\*]+)/';

    /** Matching `@param type $name description` */
    const RE_PARAM = '/@param\s([a-zA-Z0-9_\|]+)\s+\$([a-zA-Z0-9_]+)\s*([^\n\*]*?)/';

    /** Matching `@method return name(params)` */
    const RE_METHOD = '/@method\s([a-zA-Z0-9_\|]+)\s+([a-zA-Z0-9_]+)\s*\([^)]+\)/';


    /**
     * Find all the classes in a list of directories.
     *
     * This is not recursive.
     *
     * @param string[] $paths
     * @params string $filter A full namespaced class name
     * @return Generator<string>
     */
    public static function loadAllClasses(array $paths, ?string $filter = null): Generator
    {
        // Load all the files in each directory.
        foreach ($paths as $dir) {
            yield from self::loadClasses($dir, $filter);
        }
    }


    /**
     * Find all the classes in a directory, with a filter!
     *
     * This is not recursive.
     *
     * Yes, this cheats the autoloader. Idk how to do this otherwise.
     * This relies heavily on PSR-4 - if anything is out of place it won't
     * pick it up. I would think nested namespaces would also fail here too.
     *
     * This actually loads (require_once) the class. Use this carefully.
     *
     * @param string $path
     * @param string $filter A full namespaced class name
     * @return Generator<string>
     */
    public static function loadClasses(string $path, ?string $filter = null): Generator
    {
        foreach (glob($path . '/*.php') as $file) {
            // Load it.
            require_once $file;

            $class = basename($file, '.php');

            // Re-map into full paths, as best we can.
            foreach (get_declared_classes() as $full_class) {
                if (!preg_match("/{$class}$/", $full_class)) continue;

                // This should always pass.
                if (!class_exists($full_class, false)) continue;

                // All classes must subtype the filter.
                // @phpstan-ignore-next-line : phpstan doesn't like subclass checks.
                if ($filter and !is_subclass_of($full_class, $filter)) continue;
                yield $full_class;
            }
        }
    }


    /**
     * Is this callable in a static way?
     *
     * Provide an array like: `[self::class, 'methodName']`
     * Or a string like: `ns\to\Class::methodName`
     *
     * @param array|string $callable
     * @return bool
     */
    public static function isStaticCallable($callable): bool
    {
        if (is_string($callable)) {
            $callable = explode('::', $callable, 2);
        }

        if (!is_array($callable)) return false;
        if (!is_callable($callable)) return false;
        if (count($callable) !== 2) return false;

        try {
            list($class, $method) = $callable;
            $reflect = new ReflectionMethod($class, $method);

            return $reflect->isStatic() and !$reflect->isAbstract();
        }
        catch (ReflectionException $exception) {
            return false;
        }
    }


    /**
     * Get public properties of an object.
     *
     * Because PHP lacks a `IS_NOT_STATIC` flag, this method will treat an
     * absence of the `IS_STATIC` flag to mean "Don't include static properties".
     * Conversely, when specified the flag will restrict results to include
     * _only_ static properties. The exception is when no flags are specified,
     * i.e. `0` - where all properties are included, static or otherwise.
     *
     * Note, there is value to NOT using `get_object_vars` and using this
     * wrapper instead, even though it uses `get_object_vars` itself. From this
     * external context it will only include public properties. If used from
     * within an object it includes both protected + private properties.
     *
     * @param object $target
     * @param bool|int|null $flags
     *  - true: use the iterator, if available (otherwise `get_object_vars`)
     *  - false: use `get_object_vars`
     *  - int: enum `ReflectionProperty::IS` modifier types
     * @return array
     */
    public static function getProperties($target, $flags = true): array
    {
        if (is_numeric($flags) or $flags === null) {
            $flags = (int) $flags;

            $reflect = new ReflectionClass($target);
            $properties = $reflect->getProperties($flags ?: null);

            $data = [];

            $static = ($flags & ReflectionProperty::IS_STATIC);

            foreach ($properties as $property) {
                if ($flags and !$static and $property->isStatic()) continue;

                // Fix private/protected access.
                $property->setAccessible(true);

                // We need to use getValue() so to bypass any __get() magic.
                $key = $property->getName();
                $value = $property->getValue($target);

                $data[$key] = $value;
            }

            return $data;
        }
        else if ($flags and $target instanceof Traversable) {
            return iterator_to_array($target, true);
        }
        else {
            return get_object_vars($target);
        }
    }


    /**
     * Get a list of method names and their parameters.
     *
     * Note, these are only the _public_ methods of a class.
     *
     * @param string $class
     * @param string|null $filter regex filter
     * @return array
     */
    public static function getMethods(string $class, ?string $filter = null): array
    {
        $names = get_class_methods($class);
        $methods = [];

        foreach ($names as $name) {
            if ($filter and !preg_match($filter, $name)) continue;

            $method = new ReflectionMethod($class, $name);

            $comment = $method->getDocComment() ?: null;
            $defs = self::getDocParameters($comment ?? '');

            $name = $method->getName();
            $methods[$name] = [
                'name' => $name,
                'definition' => self::getMethodDefinition($method, $defs),
                'parameters' => self::getParameters($method, $defs),
                'doc' => $comment,
            ];
        }

        return $methods;
    }


    /**
     *
     * @param ReflectionFunctionAbstract|string[]|string $function
     * @return array
     */
    public static function getParameters($function, ?array $fallbacks = null): array
    {
        if (is_array($function)) {
            list($class, $method) = $function;
            $function = new ReflectionMethod($class, $method);
        }
        else if (is_string($function)) {
            $function = new ReflectionFunction($function);
        }

        if ($fallbacks === null) {
            $comment = $function->getDocComment() ?: null;
            $fallbacks = self::getDocParameters($comment ?? '');
        }

        $name = $function->getName();
        $defs = $fallbacks[$name] ?? null;

        $parameters = [];
        foreach ($function->getParameters() as $param) {
            $name = $param->getName();
            $def = $defs[$name] ?? null;

            $parameters[$name] = [
                'name' => $name,
                'definition' => self::getParameterDefinition($param, $def),
                'type' => self::getTypeName($param->getType(), $def),
            ];
        }

        return $parameters;
    }


    /**
     *
     * @param ReflectionType|null $type
     * @param string|null $fallback
     * @return string
     */
    public static function getTypeName($type, ?string $fallback = null): string
    {
        if ($type instanceof ReflectionNamedType) {
            $type = $type->getName();
        }
        else {
            $type = 'mixed';
        }

        if (!$type or $type === 'mixed') {
            return $fallback ?: 'mixed';
        }

        return $type;
    }


    /**
     * Get a php-ish string version of a method.
     *
     * @param ReflectionMethod $method
     * @param string[] $fallbacks [name => class_name]
     * @return string
     */
    public static function getMethodDefinition(ReflectionMethod $method, ?array $fallbacks = null): string
    {
        $modifiers = Reflection::getModifierNames($method->getModifiers());

        if ($fallbacks === null) {
            $fallbacks = self::getDocParameters($method->getDocComment() ?: '');
        }

        $arg_names = [];
        foreach ($method->getParameters() as $param) {
            $def = $fallbacks[$param->getName()] ?? null;
            $arg_names[] = self::getParameterDefinition($param, $def);
        }

        $return = self::getTypeName($method->getReturnType(), $fallbacks['__return'] ?? null);

        $definition = '';
        $definition .= implode(' ' , $modifiers);
        $definition .= ' ' . $method->getName();
        $definition .= '(' . implode(', ', $arg_names) . ')';
        $definition .= ': ' . $return;

        return $definition;
    }


    /**
     * Get a php-ish string version of a parameter.
     *
     * @param ReflectionParameter $parameter
     * @param string|null $fallback
     * @return string
     */
    public static function getParameterDefinition(ReflectionParameter $parameter, ?string $fallback = null): string
    {
        $value = '';

        if ($parameter->isOptional()) {
            $value .= '?';
        }

        $type = self::getTypeName($parameter->getType(), $fallback);

        $value .= $type;
        $value .= ' ';

        if ($parameter->isVariadic()) {
            $value .= '...';
        }

        if ($parameter->isPassedByReference()) {
            $value .= '&';
        }

        $value .= '$' . $parameter->getName();

        if ($parameter->isDefaultValueAvailable()) {
            $value .= ' = ' . json_encode($parameter->getDefaultValue());
        }

        return $value;
    }


    /**
     * Get a list of doc comment definitions.
     *
     * These can be anything you like.
     *
     * Special parsing for `@param` + and `@return` ids.
     *
     * TODO Parsing for `@method` ids.
     *
     * @param string $comment
     * @return array  [id, type?, name?, description]
     */
    public static function getDocDefinitions(string $comment): array
    {
        $matches = [];
        if (!preg_match_all(self::RE_DOCTYPE, $comment, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $output = [];

        foreach ($matches as $match) {
            list($line, $id, $description) = $match;

            $type = 'mixed';
            $name = '??';

            if ($id === 'param') {
                $sub = [];
                if (preg_match(self::RE_PARAM, $line, $sub)) {
                    list($_, $type, $name, $description) = $sub;
                }

                $output[$id] = [
                    'id' => $id,
                    'type' => $type,
                    'name' => $name,
                    'description' => $description,
                ];
            }
            else if ($id === 'return') {
                $sub = [];
                if (preg_match(self::RE_RETURN, $line, $sub)) {
                    list($_, $type, $description) = $sub;
                }
                $output[] = [
                    'id' => $id,
                    'type' => $type,
                    'description' => $description,
                ];
            }
            else {
                $output[] = [
                    'id' => $id,
                    'description' => $description,
                ];
            }
        }

        return $output;
    }


    /**
     * Get the params and return types from a doc comment.
     *
     * The special '__return' key will contain the return type.
     *
     * @param string $comment
     * @return array [name => type]
     */
    public static function getDocParameters(string $comment): array
    {
        $defs = self::getDocDefinitions($comment);
        $parameters = [];

        foreach ($defs as $def) {
            if ($def['id'] === 'return') {
                $parameters['__return'] = $def['type'];
                continue;
            }

            if ($def['id'] === 'param') {
                $parameters[$def['name']] = $def['type'];
                continue;
            }
        }

        return $parameters;
    }


    /**
     * Get a tidy copy of a doc comment.
     *
     * This strips any asterisks, doctype parameters, and newlines.
     *
     * @param string $comment
     * @return string
     */
    public static function getDocDescription(string $comment): string
    {
        // starts with /*
        // ends with */
        // tab *
        // @...\n
        $comment = preg_replace(
            ['/^\/\*+/', '/\*+\/$/', '/^[ \t]*\*+[ \t]*/m', '/^@.*\n/m'],
            '',
            $comment
        );

        return trim($comment);
    }


    /**
     * Get a set of `@tags` within a doc comment.
     *
     * Filtering tags will match the tag name.
     *
     * @param string $doc
     * @param array $filter
     * @return array[] [ tag => value[] ]
     * @deprecated use getDocTags()
     */
    public static function getMethodTags(string $doc, array $filter = []): array
    {
        return self::getDocTags($doc, $filter);
    }


    /**
     * Get a set of `@tags` within a doc comment.
     *
     * Filtering tags will match the tag name.
     *
     * @param string $doc
     * @param array $filter
     * @return array[] [ tag => value[] ]
     */
    public static function getDocTags(string $doc, array $filter = []): array
    {
        if (empty($doc)) {
            return [];
        }

        $tags = [];

        if ($filter) {
            $tags = array_fill_keys($filter, []);
        }

        $matches = [];
        if (!preg_match_all("/^[*\s\/]*@([^\s]+)([\t ]*[^\n]+)?/m", $doc, $matches, PREG_SET_ORDER)) {
            return $tags;
        }

        foreach ($matches as $match) {
            list( , $tag, $value ) = $match + ['', '', ''];
            if ($filter and !in_array($tag, $filter)) continue;

            $value = preg_replace('/\*+\/$/', '', $value);
            $value = trim($value);
            $tags[$tag][] = $value;
        }

        return $tags;
    }


    /**
     * Get a single set of doc tags.
     *
     * Shorthand for `getDocTags($doc, ['tag'])['tag']`.
     *
     * @param string $doc
     * @param string $tag
     * @return string[]
     */
    public static function getDocTag(string $doc, string $tag): array
    {
        $tags = self::getDocTags($doc, [$tag]);
        return $tags[$tag] ?? [];
    }

}
