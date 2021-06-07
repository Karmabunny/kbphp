<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Generator;
use Reflection;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;

/**
 * I guess this is useful.
 *
 * @package karmabunny\kb
 */
abstract class Reflect
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
     * @return Generator<int, string, mixed, void>
     */
    public static function loadAllClasses(array $paths, string $filter = null): Generator
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
     * @return Generator<int, string, mixed, void>
     */
    public static function loadClasses(string $path, string $filter = null): Generator
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
    public static function isStaticCallable($callable)
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
     * Get a list of method names and their parameters.
     *
     * @param string $class
     * @param string|null $filter regex filter
     * @return array
     */
    public static function getMethods(string $class, string $filter = null): array
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
    public static function getParameters($function, array $fallbacks = null): array
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
        $def = $fallbacks[$name] ?? null;

        $parameters = [];
        foreach ($function->getParameters() as $param) {
            $name = $param->getName();
            $def = $defs[$name] ?? null;

            $parameters[$name] = [
                'name' => $name,
                'definition' => self::getParameterDefinition($param, $def),
                'type' => (string) @$param->getType() ?: ($def ?? 'mixed'),
            ];
        }

        return $parameters;
    }


    /**
     * Get a php-ish string version of a method.
     *
     * @param ReflectionMethod $method
     * @param string[] $fallbacks [name => class_name]
     * @return string
     */
    public static function getMethodDefinition(ReflectionMethod $method, array $fallbacks = null): string
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

        $return = (string) @$method->getReturnType() ?: 'mixed';
        if ($return === 'mixed') {
            $return = $fallbacks['__return'] ?? 'mixed';
        }

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
    public static function getParameterDefinition(ReflectionParameter $parameter, string $fallback = null): string
    {
        $value = '';

        if ($parameter->isOptional()) {
            $value .= '?';
        }

        /** @var ReflectionType */
        $type = @$parameter->getType() ?: null;

        if ($type instanceof ReflectionNamedType) {
            $type = $type->getName();
        }

        if (!$type or $type === 'mixed') {
            $type = $fallback ?: 'mixed';
        }

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
}
