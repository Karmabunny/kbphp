<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;


/**
 * A JavaScript function expression.
 *
 * @package karmabunny\kb
 */
class JsFunction implements JsExpression
{

    /** @var string[] */
    public $args;


    /** @var string */
    public $function;

    /** @var bool */
    public $arrow;

    /** @var bool */
    public $inline;

    /** @var bool */
    public $strip;


    /**
     * @param mixed[] $args argument names
     * @param string $function code body
     * @param bool $arrow use arrow syntax
     * @param bool $inline an implicit return
     * @param bool $strip strip whitespace from the function body
     */
    public function __construct(array $args, string $function, bool $arrow = true, bool $inline = false, bool $strip = false)
    {
        $this->args = $args;
        $this->function = $function;
        $this->arrow = $arrow;
        $this->inline = $inline;
        $this->strip = $strip;
    }


    /** @inheritdoc */
    public function render(): string
    {
        $args = implode(', ', $this->args);

        $body = $this->function;

        if ($this->strip) {
            $body = preg_replace('/\n\w*/m', ' ', $body);
        }

        if ($this->arrow) {
            if ($this->inline) {
                return "({$args}) => ({$body})";
            }

            return "({$args}) => {{$body}}";
        }
        else {
            if ($this->inline) {
                return "function({$args}) {return ({$body});}";
            }

            return "function({$args}) {{$body}}";
        }
    }


    /** @inheritdoc */
    public function __toString()
    {
        return $this->render();
    }
}
