<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2024 Karmabunny
 */

namespace karmabunny\kb;

/**
 * An encryption tooling instance.
 */
interface EncryptInterface
{

    /**
     * Encrypts a string and returns an encrypted string that can be decoded.
     *
     * @param string $data data to be encrypted
     * @return string encrypted data
     */
    public function encode(string $data): string;


    /**
     * Encode multiple fields in a data array, modifying the array in place.
     *
     * @param array $data The complete data set we're working with
     * @param array $fields The keys of the fields to encode
     *
     * @return void
     */
    public function encodeMultipleFields(array &$data, array $fields): void;


    /**
     * Decrypts an encoded string back to its original value.
     *
     * @param string $data encoded string to be decrypted
     * @return string decrypted data
     */
    public function decode(string $data): string;


    /**
     * Decode multiple fields in a data array, modifying the array in place.
     *
     * @param array $data The complete data set we're working with
     * @param array $fields The keys of the fields to encode
     *
     * @return void
     */
    public function decodeMultipleFields(array &$data, array $fields): void;

}
