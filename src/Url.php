<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

/**
 * URL object and helper methods.
 *
 * Think of this as an unholy mush of the JS-style URL and URLSearchParams.
 *
 * @package karmabunny\kb
 */
class Url extends DataObject
{
    use UpdateVirtualTrait;

    /** @var string|null */
    public $scheme;

    /** @var string|null */
    public $host;

    /** @var string|null */
    public $port;

    /** @var string|null */
    public $user;

    /** @var string|null */
    public $pass;

    /** @var string|null */
    public $path;

    /** @var array after the question mark ? */
    public $query = [];

    /** @var string|null after the hashmark # */
    public $fragment;


    /** @inheritdoc */
    public function virtual(): array
    {
        return [
            'query' => [$this, 'setQuery'],
        ];
    }


    /**
     * Replace the query.
     *
     * @param array|string $query
     * @return static
     * @throws UrlDecodeException
     */
    public function setQuery($query)
    {
        if (is_array($query)) {
            $this->query = $query;
        }
        else {
            $this->query = self::decode($query);
        }
        return $this;
    }


    /**
     * Merge parameters into the query.
     *
     * @param array $query
     * @return static
     * @throws UrlDecodeException
     */
    public function addParams(array $query)
    {
        $this->query = array_merge($this->query, $query);
        return $this;
    }


    /**
     * Set a parameter.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setParam(string $name, $value)
    {
        $this->query[$name] = $value;
        return $this;
    }


    /**
     * Add to a query parameter.
     *
     * This will append to the existing item if it's an array, otherwise
     * converts it to an array and adds the value.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function addParam(string $name, $value)
    {
        if (array_key_exists($name, $this->query)) {
            $existing = $this->query[$name];

            if (is_array($existing)) {
                $existing[] = $value;
                $value = $existing;
            }
            else {
                $value = [ $existing, $value ];
            }
        }

        $this->query[$name] = $value;
        return $this;
    }


    /**
     * Remove a query param by name.
     *
     * @param string $name
     * @return static
     */
    public function removeParam(string $name)
    {
        unset($this->query[$name]);
        return $this;
    }


    /**
     * Does this query param exist?
     *
     * @param string $name
     * @return bool exists
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->query);
    }


    /**
     * Remove the port if the scheme infers a default one.
     *
     * @return void
     */
    public function normalize()
    {
        if ($this->scheme) {
            $default = self::getDefaultPort($this->scheme);

            if ($default == $this->port) {
                $this->port = null;
            }
        }
    }


    /**
     * Get the query string.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        if (!$this->query) {
            return '';
        }

        return self::encode($this->query);
    }


    /**
     * Build a URL from the parts.
     *
     * @return string
     */
    public function toString(bool $normalize = true): string
    {
        $url = $this;

        if ($normalize) {
            $url = clone $this;
            $url->normalize();
        }

        $out = '';

        if ($url->host) {
            if ($url->scheme) {
                $out .= $url->scheme . '://';
            }

            if ($url->user) {
                $out .= $url->user;

                if ($url->pass) {
                    $out .= ':' . $url->pass;
                }

                $out .= '@';
            }

            $out .= $url->host;

            if ($url->port) {
                $out .= ':' . $url->port;
            }
        }

        if ($url->path) {
            if ($out and strpos($url->path, '/') !== 0) {
                $out .= '/';
            }

            $out .= $url->path;
        }

        if ($url->query) {
            $out .= '?' . $url->getQueryString();
        }

        if ($url->fragment) {
            $out .= '#' . $url->fragment;
        }

        return $out;
    }


    /** @inheritdoc */
    public function __toString(): string
    {
        return $this->toString();
    }


    /**
     * Parse a string into a URL object.
     *
     * @param string $url
     * @return self
     * @throws UrlParseException
     */
    public static function parse(string $url)
    {
        $config = parse_url($url);
        if ($config === false) {
            throw new UrlParseException("Could not parse URL: {$url}");
        }
        return new self($config);
    }


    /**
     *
     * @param array $query [key => value]
     * @return string
     */
    public static function encode(array $query): string
    {
        return http_build_query($query);
    }


    /**
     *
     * @param string $query
     * @return array [key => value]
     * @throws UrlDecodeException
     */
    public static function decode(string $query): array
    {
        $result = [];
        if (!mb_parse_str($query, $result)) {
            throw (new UrlDecodeException('Failed to decode query'))
                ->setQuery($query);
        }
        return $result;
    }


    /**
     *
     * @return array
     */
    public static function getDefaultPorts(): array
    {
        static $ports;
        return $ports ?? include __DIR__ . '/config/ports.php';
    }


    /**
     *
     * @param string $scheme
     * @return null|int
     */
    public static function getDefaultPort(string $scheme)
    {
        $ports = self::getDefaultPorts();
        return $ports[$scheme] ?? null;
    }


    /**
     * Cleanly joins base urls + paths.
     *
     * A path can be just a string or a bunch of fragments.
     *
     * [
     *    'path',
     *    'to',
     *    'thing',
     *    'param' => 123,
     *    'neat' => ['one', 'two'],
     * ]
     *
     * Should return:
     * '/path/to/thing?param=123&neat[0]=one&neat[1]=two'
     *
     * @param string|array $parts
     * @return string
     */
    public static function build(...$parts): string
    {
        if (empty($parts)) return '/';

        // TODO remove this. I hate it.
        if (is_string($parts[0])) {
            $base = array_shift($parts);
        }
        else {
            $base = '/';
        }

        $url = '/';
        $path = [];

        foreach ($parts as $part) {
            if (is_array($part)) {
                $path = array_merge($path, $part);
            }
            else {
                array_push($path, $part);
            }
        }

        if (!empty($path)) {
            $parts = [];
            $query = [];

            foreach ($path as $key => $value) {
                if (is_numeric($key)) {
                    foreach (explode('/', $value) as $part) {
                        $parts[] = urlencode($part);
                    }
                }
                else {
                    $query[$key] = $value;
                }
            }

            $url .= implode('/', $parts);

            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }
        }

        return trim($base, '/') . preg_replace('/\/+/', '/', $url);
    }
}
