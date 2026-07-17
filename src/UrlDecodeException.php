<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * Url decode errors.
 *
 * @package karmabunny\kb
 */
class UrlDecodeException extends UrlException
{

    /** @var string|null */
    public ?string $query = null;

    /**
     *
     * @param string $query
     * @return static
     */
    public function setQuery(string $query): static
    {
        $this->query = $query;
        return $this;
    }
}
