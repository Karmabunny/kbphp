<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use InvalidArgumentException;

/**
 * Attach this to a property to tie it to a local method.
 *
 * The object must implement the {@see AttributeVirtualTrait} and use
 * the `applyVirtual()` helper to invoke the virtual methods.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class VirtualProperty
{

    /** @var string */
    public $method;

    /**
     *
     * @param string $method
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(string $method)
    {
        $this->method = $method;
    }


    /**
     * Create from a doc comment.
     *
     * This parses a '@virtual' tag.
     *
     * @param string $doc
     * @return self|null
     */
    public static function parseDoc(string $doc)
    {
        $tags = Reflect::getDocTags($doc, ['virtual']);
        $tags = $tags['virtual'];

        foreach ($tags as $tag) {
            $tag = trim($tag);
            return new self($tag);
        }

        return null;
    }
}
