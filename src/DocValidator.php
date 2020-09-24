<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use ReflectionClass;
use ReflectionProperty;

/**
 * Use '@var' comments to validate object properties.
 *
 * If using a class type:
 * 1. include the namespace\\to\\Class
 * 2. OR indicate where to find it with the namespaces() function
 *
 * E.g.
 *   /** @var Class //
 *   public $property;
 *
 *   public function namespaces() {
 *       return [ 'namespace\\to\\' ];
 *   }
 *
 * @package karmabunny\kb
 */
class DocValidator {

    /** @var object */
    protected $target;

    /** @var array */
    protected $errors;

    /** @var string[] */
    protected $namespaces;

    /**
     * @param object $target Object to validate.
     */
    public function __construct(object $target)
    {
        $this->target = $target;
        $this->errors = [];
        $this->namespaces = [];

        $getter = [$target, 'namespaces'];
        if (is_callable($getter)) {
            $this->namespaces = $getter();
        }
    }

    /**
     * Validate the target.
     *
     * @return bool True if valid. False if there were errors.
     * @throws Exception
     */
    public function validate()
    {
        $class = new ReflectionClass($this->target);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC ^ ReflectionProperty::IS_STATIC);

        $this->errors = [];

        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            $types = self::parseVar($comment);
            if ($types === false) continue;

            $name = $property->getName();
            $value = $property->getValue($this->target);

            $actual = self::getType($value);
            if ($actual === false) {
                throw new Exception("Property value '{$name}' has an unknown type.");
                continue;
            }

            // Special message for 'required' properties.
            if ($actual === 'null' && !in_array('null', $types)) {
                $this->errors[$name] = ['required' => 'Property is required.'];
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
            $this->errors[$name] = ["Property is {$actual} instead of {$expected}."];
        }

        return !$this->hasErrors();
    }

    /**
     * True if there were any validation errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get a list of all errors, indexed by property name.
     * Field may have multiple errors defined.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Determine if this class exists.
     *
     * Unfortunately, if the typed comment has no namespace we can't magically
     * get the namespace of that class. So users must specify a namespaces()
     * function with a list of possible namespaces in which classes can exist.
     *
     * Alternatively, put the whole namespaced class name in the doc comment.
     *
     * @param string $name
     * @return string|false
     */
    public function classExists(string &$name)
    {
        // Look up classes with namespaces.
        if (preg_match('/\/\//', $name) !== false) {
            return class_exists($name) ? $name : false;
        }

        // Search within our defined namespaces.
        foreach ($this->namespaces as $ns) {
            if (!class_exists($ns . $name)) continue;
            $name = $ns . $name;
            return $name;
        }

        // Search for built-ins.
        if (class_exists($name)) return $name;

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
     * Determine the type of a value.
     *
     * Because gettype() is deprecated apparently? But also it doesn't reflect
     * the type names anyway.
     *
     * @param mixed $value
     * @return string|false False if the type is unknown.
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
