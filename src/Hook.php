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
