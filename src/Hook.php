<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use ReflectionMethod;
use RuntimeException;

/**
 * Hooks are an iter-communication within objects, particularly for
 * communication across traits.
 *
 * Traits pose a unique problem, by nature they are an optional feature set to
 * include in a class. But at times these traits have code that needs to run in
 * certain places to operate correctly. Without rewriting those methods, there
 * is no clean way to invoke trait methods or detect which traits exists.
 *
 * Usage:
 *
 * 1. Code will invoke a hook by calling `_hook()` while declaring a hook ID
 * and any associated arguments.
 *
 * 2. A trait will attach to a hooks by using the `#[Hook]` attribute
 * (in PHP 8+) or the `@hook` tag with the same hook ID.
 *
 * @attribute hook method
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Hook extends AttributeTag
{

    /** @var string */
    public $id;


    /**
     *
     * @param string $id
     * @return void
     */
    public function __construct(string $id)
    {
        $this->id = strtolower($id);
    }


    /** @inheritdoc */
    protected static function build(string $content)
    {
        return new static($content);
    }


    /**
     *
     * @param object $object
     * @param mixed $args
     * @return void
     */
    public function run(object $object, ...$args)
    {
        if (!($this->reflect instanceof ReflectionMethod)) {
            throw new RuntimeException('This hook has not been prepared.');
        }

        $this->reflect->setAccessible(true);
        $this->reflect->invoke($object, ...$args);
    }


    /**
     *
     * @param object $object
     * @param mixed $args
     * @return void
     */
    public function __invoke(object $object, ...$args)
    {
        $this->run($object, ...$args);
    }
}
