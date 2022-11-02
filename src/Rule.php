<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;

/**
 * This represents a single rule for a property.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Rule
{

    /** @var string */
    public $name;

    /** @var array */
    public $args = [];


    /**
     *
     * @param string $rule
     * @param array $args
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(string $rule, array $args = [])
    {
        $this->name = $rule;
        $this->args = $args;
    }


    /**
     * Create a set of rules from a doc comment.
     *
     * This parses a '@rule' tag.
     *
     * @param string $doc
     * @return self[]
     */
    public static function parseDoc(string $doc): array
    {
        $tags = Reflect::getDocTags($doc, ['rule']);
        $tags = $tags['rule'];

        $rules = [];

        foreach ($tags as $tag) {
            [$rule, $args] = explode(' ', $tag, 2) + [null, null];

            $args = explode(' ', $args);
            $args = array_map('trim', $args);

            $rules[] = new self($rule, $args);
        }

        return $rules;
    }
}
