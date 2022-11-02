<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;

/**
 * This adds a scenario to an existing attribute rule.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Scenario
{

    /** @var string|null */
    public $name;


    /**
     *
     * @param string|null $name
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(string $name = null)
    {
        $this->name = $name;
    }


    /**
     * Create a set of scenarios from a doc comment.
     *
     * This parses a '@scenario' tag.
     *
     * @param string $doc
     * @return self[]
     */
    public static function parseDoc(string $doc): array
    {
        $tags = Reflect::getDocTags($doc, ['scenario']);
        $tags = $tags['scenario'];

        $scenarios = [];

        foreach ($tags as $tag) {
            $tag = trim($tag) ?: null;
            $scenarios[] = new self($tag);
        }

        return $scenarios;
    }
}
