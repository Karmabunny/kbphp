<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * A collection of id, plural, strings for a single translation message.
 */
class PoMessage
{
    /** @var string */
    public $id;

    /** @var string */
    public $plural;

    /** @var string[] */
    public $strings;

    public function __construct(string $id, string $plural, array $strings)
    {
        $this->id = strtolower(trim($id));
        $this->plural = strtolower($plural);
        $this->strings = $strings;
    }
}
