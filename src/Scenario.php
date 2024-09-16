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
 * @attribute scenario property
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Scenario extends AttributeTag
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


    /** @inheritdoc */
    protected static function build(string $args)
    {
        return new static(trim($args) ?: null);
    }

}
