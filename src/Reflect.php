<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Generator;
use Reflection;
use ReflectionException;
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
            [$class, $method] = $callable;
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

            $parameters = [];
            foreach ($method->getParameters() as $param) {
                $name = $param->getName();
                $parameters[$name] = [
                    'name' => $name,
                    'definition' => self::getParameterDefinition($param),
                    'type' => (string) @$param->getType() ?: 'mixed',
                ];
            }

            $name = $method->getName();
            $methods[$name] = [
                'name' => $name,
                'definition' => self::getMethodDefinition($method),
                'parameters' => $parameters,
            ];
        }

        return $methods;
    }


    /**
     * Get a php-ish string version of a method.
     *
     * @param ReflectionMethod $method
     * @return string
     */
    public static function getMethodDefinition(ReflectionMethod $method): string
    {
        $modifiers = Reflection::getModifierNames($method->getModifiers());
        $arg_names = array_map(
            [self::class, 'getParameterDefinition'],
            $method->getParameters()
        );
        $return = (string) @$method->getReturnType() ?: 'mixed';

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
     * @return string
     */
    public static function getParameterDefinition(ReflectionParameter $parameter): string
    {
        $value = '';

        if ($parameter->isOptional()) {
            $value .= '?';
        }

        /** @var ReflectionType */
        $type = @$parameter->getType() ?: null;
        $value .= $type instanceof ReflectionNamedType
            ? $type->getName()
            : 'mixed';

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
}
