<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionClass;
use ReflectionProperty;

/**
 * Use '@var' comments to validate object properties.
 *
 * Annoyingly, class types _must_ include the full namespace in the doc comment.
 *
 * @todo Maybe add a namespaces() function to return a list of searchable places?
 *
 * @package karmabunny/kb
 */
trait DocValidatorTrait {

    public function validate()
    {
        $class = new ReflectionClass($this);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC ^ ReflectionProperty::IS_STATIC);

        $errors = [];

        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            if ($comment === false) continue;

            $matches = [];
            if (preg_match('/@var\s+([^\s]+)/', $comment, $matches) === false) continue;

            list($_, $var) = $matches;
            $types = explode('|', $var);

            $name = $property->getName();
            $value = $property->getValue($this);

            if ($value === null) {
                $actual = 'null';
            }
            else if (is_bool($value)) {
                $actual = 'bool';
            }
            else if (is_int($value)) {
                $actual = 'int';
            }
            else if (is_float($value)) {
                $actual = 'float';
            }
            else if (is_string($value)) {
                $actual = 'string';
            }
            else if (is_object($value)) {
                $actual = 'object';
            }
            else if (is_array($value)) {
                $actual = 'array';
            }
            else {
                // TODO warning?
                error_log("Property value '{$name}' has an unknown type.");
                continue;
            }

            // Special message for 'required' properties.
            if ($actual === 'null' && !in_array('null', $types)) {
                $errors[$name] = ['required' => 'Property is required.'];
                continue;
            }

            foreach ($types as $expected) {
                if ($expected === 'float' and $actual === 'int') {
                    continue 2;
                }

                if ($expected === 'true' and $value === true) {
                    continue 2;
                }

                if ($expected === 'false' and $value === false) {
                    continue 2;
                }

                if ($expected === $actual) {
                    continue 2;
                }

                if (class_exists($expected) and $value instanceof $expected) {
                    continue 2;
                }
            }

            $errors[$name] = ["Property is {$actual} instead of {$var}."];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     *
     * @param string $name
     * @return string|false
     */
    private static function classExists(string &$name)
    {
        if (class_exists($name)) return $name;

        if (is_callable(['self', 'namespaces'])) {
            $namespaces = self::namespaces();

            foreach ($namespaces as $ns) {
                if (!class_exists($ns . $name)) continue;
                $name = $ns . $name;
                return $name;
            }
        }

        return false;
    }
}

