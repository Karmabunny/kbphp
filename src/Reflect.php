<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Reflection;
use ReflectionParameter;
use ReflectionMethod;

/**
 * I guess this is useful.
 *
 * @package karmabunny\kb
 */
abstract class Reflect {

    /**
     * Get a list of method names and their parameters.
     *
     * @param string $class
     * @return array
     */
    public static function getMethods(string $class, string $filter = null): array
    {
        $names = get_class_methods($class);
        $methods = [];

        foreach ($names as $name) {
            if ($filter and strpos($name, $filter) !== 0) continue;

            $method = new ReflectionMethod($class, $name);
            $args = $method->getParameters();

            $modifiers = Reflection::getModifierNames($method->getModifiers());
            $arg_names = array_map(['self', 'getParameterDefinition'], $args);
            $return = (string) @$method->getReturnType() ?: 'mixed';

            $definition = '';
            $definition .= implode(' ' , $modifiers);
            $definition .= ' ' . $name;
            $definition .= '(' . implode(', ', $arg_names) . ')';
            $definition .= ': ' . $return;

            $methods[$name] = [
                'definition' => $definition,
                'name' => $name,
                'modifiers' => $modifiers,
                'return' => $return,
                'parameter_names' => array_map(function($arg) {
                    return $arg->getName();
                }, $args),
                'parameter_types' => array_map(function($arg) {
                    return @$arg->getType() ?: 'mixed';
                }, $args),
                'parameters' => $arg_names,
            ];
        }

        return $methods;
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

        $value .= (string) @$parameter->getType() ?: 'mixed';
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
