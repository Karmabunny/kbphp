<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use Error;
use JsonException;
use TypeError;

/**
 * This represents a single rule for a property.
 *
 * @attribute rule property
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Rule extends AttributeTag
{

    /** @var string */
    public $name;

    /** @var array */
    public $args = [];


    /**
     *
     * @param string $rule
     * @param mixed $args
     * @return void
     */
    public function __construct(string $rule, ...$args)
    {
        $this->name = $rule;
        $this->args = $args;
    }


    /** @inheritdoc */
    protected static function build(string $content)
    {
        [$rule, $args] = explode(' ', $content, 2) + ['', ''];

        try {
            $args = Json::decode("[{$args}]");
            return new static($rule, ...$args);
        }
        catch (JsonException $exception) {
            throw new Error("Error parsing rule: {$content}", 0, $exception);
        }
    }

}
