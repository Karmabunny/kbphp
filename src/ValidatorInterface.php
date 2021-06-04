<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 *
 * @package karmabunny\kb
 */
interface ValidatorInterface
{

    public function validate(): bool;

    public function hasErrors(): bool;

    public function getErrors(): array;
}
