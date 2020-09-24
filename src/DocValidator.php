<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use ReflectionClass;
use ReflectionProperty;

class DocValidator {

    /** @var object */
    protected $target;

    /**
     *
     * @param object $target
     */
    public function __construct(object &$target)
    {
        $this->target = $target;
    }

    /**
     *
     * @throws ValidationException
     */
    public function validate()
    {
        $class = new ReflectionClass($this->target);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC ^ ReflectionProperty::IS_STATIC);

        $errors = [];

        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            $types = self::parseVar($comment);
            if ($types === false) continue;

            $name = $property->getName();
            $value = $property->getValue($this->target);

            $actual = self::getType($value);
            if ($actual === false) {
                throw new \Exception("Property value '{$name}' has an unknown type.");
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

                if ($this->classExists($expected) and $value instanceof $expected) {
                    continue 2;
                }
            }

            // Still here? Must be broken.
            $expected = implode('|', $types);
            $errors[$name] = ["Property is {$actual} instead of {$expected}."];
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }


    /**
     * Determine if this class exists.
     *
     * Unfortunately, if the typed comment has no namespace we can't magically get the namespace of a
     *
     * @param string $name
     * @return string|false
     */
    public function classExists(string &$name)
    {
        if (class_exists($name)) return $name;

        $getter = [$this->target, 'namespaces'];
        if (is_callable($getter)) {
            $namespaces = $getter();

            foreach ($namespaces as $ns) {
                if (!class_exists($ns . $name)) continue;
                $name = $ns . $name;
                return $name;
            }
        }

        return false;
    }


    /**
     * Extract the types from a doc comment.
     *
     * Like: `@var type1|type2`
     * Returns an array of all type strings.
     *
     * @param string|false $comment
     * @return string[]|false False if invalid.
     */
    public static function parseVar($comment)
    {
        if ($comment === false) return false;

        $matches = [];
        if (preg_match('/@var\s+([^\s]+)/', $comment, $matches) === false) {
            return false;
        };

        list($_, $var) = $matches;
        return explode('|', $var);
    }


    /**
     *
     * @param mixed $value
     * @return string|false
     */
    public static function getType($value)
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_string($value)) {
            return 'string';
        }
        if (is_object($value)) {
            return 'object';
        }
        if (is_array($value)) {
            return 'array';
        }

        return false;
    }

}
