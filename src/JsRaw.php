<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

/**
 * A raw JavaScript expression.
 *
 * @package karmabunny\kb
 */
class JsRaw implements JsExpression
{

    /** @var string */
    public $value;


    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }


    /** @inheritdoc */
    public function render(): string
    {
        return $this->value;
    }


    /** @inheritdoc */
    public function __toString()
    {
        return $this->render();
    }
}
