<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2024 Karmabunny
 */
namespace karmabunny\kb;

use karmabunny\interfaces\EncryptInterface;
use karmabunny\interfaces\ValidatesInterface;

/**
 * The Encrypt library provides two-way encryption of text and binary strings
 * using the openSSL extension.
 */
class Encrypt implements EncryptInterface, ValidatesInterface
{

    /** @var array */
    protected $config;


    /**
     * Returns a singleton instance of Encrypt.
     *
     * @param array $config configuration options
     * @return Encrypt
     */
    public static function instance(array $config = []): Encrypt
    {
        static $instances = [];

        // Sort by key to ensure consistent hash
        $sorted_config = $config;
        ksort($sorted_config);
        $config_hash = hash('sha256', json_encode($sorted_config));

        // Create the singleton
        if (!isset($instances[$config_hash])) {
            $instances[$config_hash] = new Encrypt($config);
        }

        return $instances[$config_hash];
    }


    /**
     * Loads encryption configuration and validates the data.
     *
     * @param array $config Encrypt configuration including key, cipher and iv_size
     * @throws ValidationException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validate();
    }


    /** @inheritdoc */
    public function validate(?string $scenario = null): void
    {
        if (empty($this->config['key'])) {
            throw new ValidationException('No encryption key provided');
        }

        if (empty($this->config['cipher'])) {
            throw new ValidationException('No encryption cipher provided');
        }

        if (empty($this->config['iv_size'])) {
            throw new ValidationException('No encryption iv_size provided');
        }

        $ciphers = openssl_get_cipher_methods(true);

        if (!in_array($this->config['cipher'], $ciphers)) {
            throw new ValidationException("Invalid encrypt cipher '{$this->config['cipher']}'");
        }
    }


    /**
     * Encrypts a string and returns an encrypted string that can be decoded.
     *
     * @param string $data data to be encrypted
     * @return string encrypted data
     */
    public function encode(string $data): string
    {
        $iv = openssl_random_pseudo_bytes($this->config['iv_size']);

        // Encrypt the data using the configured options and generated iv
        $data = openssl_encrypt($data, $this->config['cipher'], $this->config['key'], 0, $iv);

        // Use base64 encoding to convert to a string
        return base64_encode($iv . $data);
    }


    /**
     * Encode multiple fields in a data array, modifying the array in place.
     *
     * @param array $data The complete data set we're working with
     * @param array $fields The keys of the fields to encode
     *
     * @return void
     */
    public function encodeMultipleFields(array &$data, array $fields): void
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encode($data[$field]);
            }
        }
    }


    /**
     * Decrypts an encoded string back to its original value.
     *
     * @param string $data encoded string to be decrypted
     * @return string decrypted data
     */
    public function decode(string $data): string
    {
        // Convert the data back to binary
        $data = base64_decode($data);

        // Extract the initialization vector from the data
        $iv = substr($data, 0, $this->config['iv_size']);

        // Remove the iv from the data
        $data = substr($data, $this->config['iv_size']);

        // Return the decrypted data, trimming the \0 padding bytes from the end of the data
        return rtrim(openssl_decrypt($data, $this->config['cipher'], $this->config['key'], 0, $iv), "\0");
    }


    /**
     * Decode multiple fields in a data array, modifying the array in place.
     *
     * @param array $data The complete data set we're working with
     * @param array $fields The keys of the fields to decode
     *
     * @return void
     */
    public function decodeMultipleFields(array &$data, array $fields): void
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->decode($data[$field]);
            }
        }
    }
}
