<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use Generator;
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
 * ```
 *   // @var Class
 *   public $property;
 *
 *   public function namespaces() {
 *       return [ 'namespace\\to\\' ];
 *   }
 * ```
 * @package karmabunny\kb
 */
class DocValidator implements Validator {

    /** @var object */
    protected $target;

    /** @var array */
    protected $errors;

    /** @var string[] */
    protected $namespaces;


    /**
     * Create a new validator.
     *
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
     */
    public function validate(): bool
    {
        // Start fresh.
        $this->errors = [];

        // For all doc properties.
        foreach (self::getDocTypes($this->target) as $type) {
            $this->checkRequired($type);
            $this->checkTypes($type);
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
     *
     * Field may have multiple errors defined.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }


    /**
     * Check if the doc type is required.
     *
     * This will add invalid types to the errors.
     *
     * @param DocType $type
     * @return bool True if valid.
     */
    public function checkRequired(DocType $type): bool
    {
        $actual = $type->getValueType();
        $expected = $type->getCommentTypes();

        if ($actual === 'null' and !in_array('null', $expected)) {
            $this->errors[$type->name]['required'] = 'Property is required.';
            return false;
        }

        return true;
    }


    /**
     * Check the validity of a doc type.
     *
     * This will add invalid types to the errors.
     *
     * @param DocType $type
     * @return bool True if valid.
     */
    public function checkTypes(DocType $type): bool
    {
        $actual = $type->getValueType();
        $expected_types = $type->getCommentTypes();

        // Loop over all the doc types.
        // If just one of them matches then we're golden.
        foreach ($expected_types as &$expected) {
            // echo "valid: {$type->name} - $expected was $actual\n";
            if ($this->isValid($expected, $type->value)) {
                return true;
            }

            // Rewrite invalid classes with full names.
            if ($class = $this->lookupClass($expected)) {
                $expected = '\\' . trim($class, '\\');
            }
        }

        // Still here? Must be invalid.
        $expected = implode('|', $expected_types);
        $this->errors[$type->name][] = "Expected {$expected} instead of {$actual}.";

        return false;
    }


    /**
     * Get a list of 'doc types' of an object.
     *
     * @param object $target
     * @return Generator<DocType>
     */
    public static function getDocTypes($target): Generator
    {
        $class = new ReflectionClass($target);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if ($property->isStatic()) continue;

            $name = $property->getName();
            $comment = $property->getDocComment() ?: '';
            $value = $property->getValue($target);

            yield new DocType([
                'name' => $name,
                'comment' => $comment,
                'value' => $value,
            ]);
        }
    }


    /**
     * Determine if the 'value' and 'actual' type match the 'expected' type.
     *
     * This does not modify the errors.
     *
     * @param string $expected type name
     * @param mixed $value real value
     * @return bool True if valid.
     */
    protected function isValid(string $expected, $value): bool
    {
        if ($value === null and $expected === 'null') {
            return true;
        }

        // Number checks are special.
        // $value is one of: string, int, float.
        if (is_numeric($value)) {

            // Expectant ints can floats as long as they're 'whole'.
            if ($expected === 'int' and floatval($value) == intval($value)) {
                return true;
            }

            // Expectant floats can receive anything.
            if ($expected === 'float') {
                return true;
            }
        }

        if (is_string($value) and $expected === 'string') {
            return true;
        }

        if (is_bool($value) and $expected === 'bool') {
            return true;
        }

        if ($value === true and $expected === 'true') {
            return true;
        }

        if ($value === false and $expected === 'false') {
            return true;
        }

        if (
            is_object($value) and
            ($class = $this->lookupClass($expected)) and
            $value instanceof $class
        ) {
            return true;
        }

        if (
            is_array($value) and
            $this->isValidArray($expected, $value)
        ) {
            return true;
        }

        if (is_resource($value) and $expected === 'resource') {
            return true;
        }

        return false;
    }


    /**
     * Determine if all items of an array match the expected type.
     *
     * This does not modify the errors.
     *
     * @param string $expected type name
     * @param array $values list of real array values.
     * @return bool True if valid.
     */
    protected function isValidArray(string $expected, array $values): bool
    {
        // Get the item type - like item[].
        // Strip the [] bit.
        $matches = [];
        if (!preg_match('/^(.+)\[\]$/', $expected, $matches)) return false;
        $expected = $matches[1];

        // This is _slightly_ different to the loop in checkTypes().
        // This halts when there is an invalid type.
        foreach ($values as $value) {
            // Prevent recursion - we're only going 1-level deep.
            if (is_array($value) and $expected === 'array') {
                continue;
            }
            if (is_array($value) or $expected === 'array') {
                return false;
            }

            // Jump in.
            if (!$this->isValid($expected, $value)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Determine the full namespaced version of this class.
     *
     * Unfortunately, if the typed comment has no namespace we can't magically
     * get the namespace of that class. At least, not without triggering the
     * autoloader in some way.
     *
     * So users must specify a `namespaces()` method in which classes may exist.
     *
     * Alternatively, put the whole namespaced class name in the doc comment.
     *
     * @param string $name
     * @return string|null null if not found.
     */
    protected function lookupClass(string $name)
    {
        if (!trim($name)) return null;

        // Look up classes with namespaces.
        if (strpos('/\/\//', $name) !== false) {
            return class_exists($name) ? $name : null;
        }

        // Search within our defined namespaces.
        foreach ($this->namespaces as $ns) {
            if (!class_exists($ns . $name)) continue;
            $name = $ns . $name;
            return $name;
        }

        // Search for built-ins.
        if (class_exists($name)) return $name;

        return null;
    }
}
