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
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Hook
{
    /** @var string */
    public $id;

    /** @var ReflectionMethod|null */
    public $target;


    /**
     *
     * @param string $id
     * @return void
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }


    /**
     *
     * @param ReflectionMethod $target
     * @return void
     */
    public function prepare(ReflectionMethod $target)
    {
        $this->target = $target;
    }


    /**
     *
     * @return string
     */
    public function getId(): string
    {
        return strtolower($this->id);
    }


    /**
     *
     * @param object|null $object
     * @param mixed $args
     * @return void
     */
    public function run($object, ...$args)
    {
        if (!$this->target) {
            throw new RuntimeException('This hook has not been prepared.');
        }

        // Shhhh.
        if (!$this->target->isStatic() and !$object) {
            return;
        }

        $this->target->invokeArgs($object, $args);
    }


    /**
     *
     * @param object|null $object
     * @param mixed $args
     * @return void
     */
    public function __invoke($object, ...$args)
    {
        $this->run($object, ...$args);
    }
}
