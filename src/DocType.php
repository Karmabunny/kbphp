<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This represents a property in class, typed by it's doc comment.
 *
 * Such as `@var TypeName|null`
 *
 * This is an internal detail. I don't expect anyone to use this outside of
 * the DocValidator.
 *
 * @package karmabunny\kb
 */
class DocType extends Collection
{
    /** @var string */
    public string $name;

    /** @var string */
    public string $comment;

    /** @var mixed */
    public mixed $value;

    /** @var string[]|null */
    private array|null $_doc_types = null;


    /**
     *
     * @return string[]
     */
    public function getCommentTypes(): array
    {
        if (!isset($this->_doc_types)) {
            $this->_doc_types = self::parseCommentTypes($this->comment);
        }

        return $this->_doc_types;
    }


    /**
     * This is not for comparison, only pretty messages.
     *
     * @return string
     */
    public function getValueType(): string
    {
        return self::parseValueType($this->value);
    }


    /**
     * Pretty type name.
     *
     * @param mixed $value
     * @return string
     */
    public static function parseValueType(mixed $value): string
    {
        if (is_array($value)) {
            $value = Arrays::first($value);

            // Boring, can't tell.
            if ($value === null) return 'array';

            // Recurse.
            return self::parseValueType($value) . '[]';
        }

        // Consistent namespace prefix thingy.
        if (is_object($value)) {
            return '\\' . trim(get_class($value), '\\');
        }

        // Split on spaces:
        // - 'unknown type' becomes 'unknown'
        // - 'resources (closed)' becomes 'resource'
        list($type) = explode(' ', strtolower(gettype($value)), 1);

        switch ($type) {
            case 'double': return 'float';
            case 'integer': return 'int';
            case 'boolean': return 'bool';
            default: return $type;
        }
    }


    /**
     * Extract the types from a doc comment.
     *
     * A comment can have one or many types (union).
     *
     * Like: `@var type1|type2`
     *
     * Returns an array of all type strings.
     *
     * @param string $comment
     * @return string[] empty if missing/invalid.
     */
    public static function parseCommentTypes(string $comment): array
    {
        if (!$comment) return [];

        $matches = [];
        if (preg_match('/@var\s+([^\s]+)/', $comment, $matches) === false) {
            return [];
        };

        return explode('|', $matches[1]);
    }

}
