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
    public $query;

    /**
     *
     * @param string $query
     * @return static
     */
    public function setQuery(string $query)
    {
        $this->query = $query;
        return $this;
    }
}
