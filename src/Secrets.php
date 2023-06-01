<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;


/**
 * Identify secrets in strings.
 *
 * Mask or remove them.
 * Whatever you like.
 *
 * @link https://github.com/Yelp/detect-secrets/
 * @package karmabunny\kb
 */
class Secrets extends DataObject
{
    use UpdateTidyTrait;

    const RULE_KEY_PASSWORD = '(?i:password|passwd)';
    const RULE_KEY_SECRET = '(?:ikey|secret)';
    const RULE_KEY_TOKEN = '(?:itoken|salt|nonce)';
    const RULE_KEY_CVV = '(?:icvv|cvn|ccv)';

    const RULE_HTTP_AUTH = '^(?:ibasic|bearer)';
    const RULE_JWT = '^eyJ[^.]\.';
    const RULE_SSH_PGP = '^BEGIN.*PRIVATE KEY';
    const RULE_BYCRYPT_HASH = '^\$2[aby]\$..*\$';
    const RULE_GITHUB = '^(?:ghp|gho|ghu|ghs|ghr)';
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
    public function update($config)
    {
        parent::update($config);
        $this->key_pattern = $this->buildPattern($this->key_rules);
        $this->value_pattern = $this->buildPattern($this->value_rules);
    }


    /**
     *
     * @param string $pattern
     * @return void
     */
    public function addKeyRule(string $pattern)
    {
        $this->key_rules[] = $pattern;
        $this->key_pattern = $this->buildPattern($this->key_rules);
    }


    /**
     *
     * @param string $pattern
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
     * @return bool
     */
    public function isSecretValue($item): bool
    {
        if (!is_string($item)) {
            return false;
        }

        if (empty($item)) {
            return false;
        }

        // Right?
        if (strlen($item) == 2) {
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

        // Or maybe it's maybelline. Or hex.
        if (preg_match('/^[0-9a-f]*$/', $item)) {
            if ($this->hex) {
                return true;
            }

            if (preg_match($this->value_pattern, pack('H*', $item))) {
                return true;
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
        if (!is_string($item)) return false;
        return $this->isSecretKey($item) or $this->isSecretValue($item);
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
        $process = function (&$value, $key) {
            if ($this->isSecretKey($key)) {
                $value = $this->getMask($value);
            }

            if ($this->isSecretValue($value)) {
                $value = $this->getMask($value);
            }
        };

        if ($recursive) {
            array_walk_recursive($item, $process);
        }
        else {
            array_walk($item, $process);
        }

        return $item;
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
        if ($recursive) {
            $arrayIterator = new \RecursiveArrayIterator($item);
            $recursiveIterator = new \RecursiveIteratorIterator($arrayIterator, \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($recursiveIterator as $key => $value) {
                // TODO none of this works...
                if ($this->isSecretKey($key)) {
                    $arrayIterator->offsetUnset($key);
                }

                if ($this->isSecretValue($value)) {
                    $arrayIterator->offsetUnset($key);
                }
            }

            return $arrayIterator->getArrayCopy();
        }
        else {
            foreach ($item as $key => $value) {
                if ($this->isSecretKey($key)) {
                    unset($item[$key]);
                }

                if ($this->isSecretValue($value)) {
                    unset($item[$key]);
                }
            }

            return $item;
        }
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
