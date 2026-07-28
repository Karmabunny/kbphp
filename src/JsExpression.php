<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

namespace karmabunny\kb;

/**
 * For use with Js::encode().
 */
interface JsExpression
{

    /**
     * Render the expression as a string.
     *
     * @return string
     */
    public function render(): string;
}
