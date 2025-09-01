<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use JsonException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Identify secrets in strings.
 *
 * Mask or remove them.
 * Whatever you like.
 *
 * This comes preloaded with a set of rules to help you tidy up your mess.
 *
 * Best use case:
 *
 * ```
 * $safe = Secrets::create()->mask($_POST);
 * ```
 *
 * @link https://github.com/Yelp/detect-secrets/
 * @package karmabunny\kb
 */
class Secrets extends DataObject
{
    use UpdateTidyTrait;


    const RULE_KEY_PASSWORD = '(?i:password|passwd)';
    const RULE_KEY_SECRET = '(?i:key|secret)';
    const RULE_KEY_TOKEN = '(?i:token|salt|nonce|jwt)';
    const RULE_KEY_CVV = '(?i:cvv|cvn|ccv)';

    const RULE_HTTP_AUTH = '^(?i:basic|bearer)\w(.*)';
    const RULE_HTTP_INLINE = ':\/\/[^:]+:[^@]+@';
    const RULE_JWT = '^eyJ[^.]+\..';
    const RULE_SSH_PGP = '^BEGIN.*PRIVATE KEY';
    const RULE_BYCRYPT_HASH = '^\$2[aby]\$.*\$';
    const RULE_GITHUB = '^(?:ghp|github_pat|gho|ghu|ghs|ghr)';
    const RULE_AWS = '^AKIA[0-9A-Z]{16}';
    const RULE_AWS_KEY = '^aws.{0,20}(?:key|pwd|pw|password|pass|token)';
    const RULE_SENDGRID = '^SG\..{22}\..{43}';
    const RULE_SQUARE = '^sq0csp-';
    const RULE_STRIPE = '^[rs]k_live';


    const RULE_KEYS = [
        self::RULE_KEY_PASSWORD,
        self::RULE_KEY_SECRET,
        self::RULE_KEY_TOKEN,
        self::RULE_KEY_CVV,
    ];


    const RULE_VALUES = [
        self::RULE_HTTP_AUTH,
        self::RULE_HTTP_INLINE,
        self::RULE_JWT,
        self::RULE_SSH_PGP,
        self::RULE_BYCRYPT_HASH,
        self::RULE_GITHUB,
        self::RULE_AWS,
        self::RULE_AWS_KEY,
        self::RULE_SENDGRID,
        self::RULE_SQUARE,
        self::RULE_STRIPE,
    ];


    /** @var string[] */
    public $key_rules;

    /** @var string[] */
    public $value_rules;

    /** @var string */
    public $key_pattern;

    /** @var string */
    public $value_pattern;

    /**
     * Whether to treat _all_ base64 strings as secrets.
     *
     * If not enabled, base64 strings that contain matching patterns are
     * still removed.
     *
     * If enabled, all base64 strings are removed.
     *
     * Default: false.
     *
     * @var bool
     */
    public $base64 = false;

    /**
     * Whether to treat _all_ hex strings as secrets.
     *
     * If not enabled, hex strings that contain matching patterns are
     * still removed.
     *
     * If enabled, all hex strings are removed.
     *
     * Default: false.
     *
     * @var bool
     */
    public $hex = false;

    /**
     * Create masks with fixed sizes.
     *
     * @var int|null
     */
    public $mask_length = 16;


    /**
     * Shorthand, create a sanitizer using the builtin rules.
     *
     * @param array $config override settings
     * @return Secrets
     */
    public static function create(array $config = [])
    {
        $config['key_rules'] = $config['key_rules'] ?? self::RULE_KEYS;
        $config['value_rules'] = $config['value_rules'] ?? self::RULE_VALUES;
        return new self($config);
    }


    /** @inheritdoc */
    public function update($config): void
    {
        parent::update($config);
        $this->key_pattern = $this->buildPattern($this->key_rules);
        $this->value_pattern = $this->buildPattern($this->value_rules);
    }


    /**
     * Add a new key rule.
     *
     * @param string $pattern regex
     * @return void
     */
    public function addKeyRule(string $pattern)
    {
        $this->key_rules[] = $pattern;
        $this->key_pattern = $this->buildPattern($this->key_rules);
    }


    /**
     * Add a new value rule.
     *
     * @param string $pattern regex
     * @return void
     */
    public function addValueRule(string $pattern)
    {
        $this->value_rules[] = $pattern;
        $this->value_pattern = static::buildPattern($this->value_rules);
    }


    /**
     * Does this key identify a secret?
     *
     * @param mixed $item
     * @return bool
     */
    public function isSecretKey($item): bool
    {
        if (!is_string($item)) {
            return false;
        }

        if (empty($item)) {
            return false;
        }

        if (preg_match($this->key_pattern, $item)) {
            return true;
        }

        return false;
    }


    /**
     * Does this value look like a secret?
     *
     * @param mixed $item
     * @param bool $recursive - process url/json strings
     * @return bool
     */
    public function isSecretValue($item, bool $recursive = true): bool
    {
        if (!is_string($item)) {
            return false;
        }

        if (empty($item)) {
            return false;
        }

        // What secret is shorter than 3 chars?
        if (strlen($item) < 3) {
            return false;
        }

        if (preg_match($this->value_pattern, $item)) {
            return true;
        }

        // Maybe it's a base64 string.
        $base64 = base64_decode($item, false);

        if ($base64) {
            if ($this->base64) {
                return true;
            }

            if (preg_match($this->value_pattern, $base64)) {
                return true;
            }
        }

        $hex = preg_match('/^[0-9a-f]+$/i', $item)
            ? pack('H*', $item)
            : false;

        // Or maybe it's maybelline. Or hex.
        if (is_string($hex) and strlen($hex) > 0) {
            if ($this->hex) {
                return true;
            }

            if (preg_match($this->value_pattern, $hex)) {
                return true;
            }
        }

        if ($recursive) {
            $items = [];
            $items[] = $item;

            if ($base64) {
                $items[] = $base64;
            }
            if ($hex) {
                $items[] = $hex;
            }

            foreach ($items as $item) {
                // Perhaps it's a url.
                $query = parse_url($item, PHP_URL_QUERY);
                $query = $query ?: ltrim($item, '?');

                // Perhaps it's urlencoded.
                // I only really care if it's got an equals and no whitespace.
                // The PHP decoder is quite liberal and doesn't effectively
                // _identify_ query strings. Although not unfair, in truth
                // anything can be a query string.
                if (
                    !preg_match('/[ \t]/', $query)
                    and preg_match('/.=./', $query)
                ) {
                    try {
                        $query = Url::decode($query);

                        if ($this->hasSecret($query)) {
                            return true;
                        }
                    }
                    catch (UrlDecodeException $exception) {
                        // ignore.
                    }
                }

                // Or perhaps it's json.
                try {
                    $json = Json::decode($item);

                    if (is_string($json)) {
                        if ($this->isSecretValue($json, false)) {
                            return true;
                        }
                    }
                    else if (is_array($json)) {
                        if ($this->hasSecret($json)) {
                            return true;
                        }
                    }
                }
                // phpcs:ignore
                catch (JsonException $exception) {
                    // ignore.
                }
            }
        }

        return false;
    }


    /**
     * Does this item look like a secret, either key or value?
     *
     * @param mixed $item
     * @return bool
     */
    public function isSecret($item): bool
    {
        if (!is_string($item)) {
            return false;
        }

        if (empty($item)) {
            return false;
        }

        if ($this->isSecretKey($item)) {
            return true;
        }

        if ($this->isSecretValue($item)) {
            return true;
        }

        return false;
    }


    /**
     * Mask any secrets in the given array.
     *
     * Note, recursive mode cannot dive into objects.
     *
     * @param array $item
     * @param bool $recursive
     * @return array
     */
    public function mask(array $item, bool $recursive = true): array
    {
        $process = function ($value, $key) {
            if ($this->isSecretKey($key)) {
                // Dive into arrays and nuke everything.
                // This only needs leaf nodes.
                if (is_array($value)) {
                    return Arrays::mapRecursive($value, function($value) {
                        return $this->getMask($value);
                    });
                }

                // Otherwise we assume it's scalar?
                // TODO ...what if it's not?
                return $this->getMask($value);
            }

            if ($this->isSecretValue($value)) {
                return $this->getMask($value);
            }

            return $value;
        };

        if ($recursive) {
            return Arrays::mapRecursive($item, $process, Arrays::CHILD_FIRST);
        }
        else {
            return Arrays::mapWithKeys($item, $process);
        }
    }


    /**
     * Remove any secrets in the given array.
     *
     * Note, recursive mode cannot dive into objects.
     *
     * @param array $item
     * @param bool $recursive - process nested arrays
     * @return array
     */
    public function clean(array $item, bool $recursive = true): array
    {
        $process = function ($value, $key) {
            return (
                !$this->isSecretKey($key)
                and !$this->isSecretValue($value)
            );
        };

        if ($recursive) {
            return Arrays::filterRecursive($item, $process, Arrays::SELF_FIRST);
        }
        else {
            return array_filter($item, $process, ARRAY_FILTER_USE_BOTH);
        }
    }


    /**
     * Does this array contain a secret?
     *
     * Note, this will recursively search all nodes (including leaves) but
     * will _not_ recurse into decoded URL/JSON strings.
     *
     * For that reason, this isn't a public method because it's confusing
     * outside of this internal context and not particularly useful otherwise.
     *
     * @param array $array
     * @return bool
     */
    protected function hasSecret(array $array): bool
    {
        // Remember: array_walk_recursive and our own recursiveFilter only
        // visits leaf nodes. But here we definitely want to be checking
        // everything because we're reading through a JSON/URL string.

        $arrayIterator = new RecursiveArrayIterator($array);
        $recursiveIterator = new RecursiveIteratorIterator($arrayIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($recursiveIterator as $key => $item) {
            if ($this->isSecretKey($key)) {
                return true;
            }

            if ($this->isSecretValue($item, false)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Create a masked version of the value.
     *
     * TODO use php8 sensitive attributes here.
     *
     * @param string $value
     * @return string
     */
    protected function getMask(string $value): string
    {
        $length = $this->mask_length ?: strlen($value);
        return str_repeat('*', $length);
    }


    /**
     * Build a regex pattern from a list of rules.
     *
     * @param array $rules
     * @param string $delimiter
     * @return string
     */
    protected function buildPattern(array $rules, string $delimiter = '/'): string
    {
        return $delimiter . '(?:' . implode('|', $rules) . ')' . $delimiter;
    }

}
