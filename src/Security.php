<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Exception;
use InvalidArgumentException;


/**
 * Functions for implementing security, including secure random numbers
 *
 * @package karmabunny\kb
 */
class Security
{

    /**
    * Password algorithms
    **/
    const PASSWORD_SHA_SALT = 2;
    const PASSWORD_BCRYPT12 = 3;
    const PASSWORD_SHA_SALT_5000 = 5;

    // Read-only algorithms, for data migration
    const PASSWORD_SHA = 1;
    const PASSWORD_PLAIN = 4;

    /**
     * Returns a binary string of random bytes
     *
     * @param int $length
     * @return string Binary string
     */
    public static function randBytes($length)
    {
        $length = (int) $length;
        if ($length < 8) {
            throw new InvalidArgumentException('Insufficient length; min is 8 bytes');
        }

        return random_bytes($length);
    }


    /**
     * Return a single random byte
     *
     * @return string Binary string; one byte
     */
    public static function randByte()
    {
        static $buffer = [];
        if (count($buffer) === 0) {
            $buffer = str_split(self::randBytes(256));
        }
        return array_pop($buffer);
    }


    /**
     * Returns a string of random characters
     *
     * @param int $length
     * @return string
     */
    public static function randStr($length = 16, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
    {
        $num_chars = strlen($chars) * 1.0;
        $mask = 256 - (256 % $num_chars);

        $result = '';
        do {
            $val = self::randByte();
            if (ord($val) >= $mask) {
                continue;
            }
            $result .= $chars[ord($val) % $num_chars];
        } while (strlen($result) < $length);

        return $result;
    }


    /**
     * Constant-time string comparison
     *
     * @param string $known_string The known hash
     * @param string $user_string The user supplied hash to check
     * @return bool True if the strings match, false if they don't
     */
    public static function compareStrings($known_string, $user_string)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known_string, $user_string);
        } else {
            $ret = strlen($known_string) ^ strlen($user_string);
            $ret |= array_sum(unpack("C*", $known_string ^ $user_string));
            return !$ret;
        }
    }


    /**
     * Check a password hash against an entered password
     *
     * @param string $known_hash The known hash to check against, typically from the database
     * @param int $algorithm Password algorithm, {@see self}
     * @param string $salt
     * @param string $user_string Password which was entered by the user, to check against the stored hash
     * @return bool True if the password matches, false if it doesn't
     */
    public static function doPasswordCheck($known_hash, $algorithm, $salt, $user_string)
    {
        switch ($algorithm) {
            case self::PASSWORD_SHA:
                $expected = sha1($user_string);
                return Security::compareStrings($known_hash, $expected);

            case self::PASSWORD_SHA_SALT:
                $expected = sha1(sha1($salt . $user_string . $salt));
                return Security::compareStrings($known_hash, $expected);

            case self::PASSWORD_SHA_SALT_5000:
                $expected = $salt . $user_string . $salt;
                for ($i = 1; $i <= 5000; $i++) {
                    $expected = sha1($expected);
                }
                return Security::compareStrings($known_hash, $expected);

            case self::PASSWORD_BCRYPT12:
                // The entire known password is used as a salt when generating the expected hash
                $expected = crypt($user_string, $known_hash);
                return Security::compareStrings($known_hash, $expected);
        }

        return false;
    }


    /**
     * Return a hashed password, password algorithm, and salt, for inserting into the database
     *
     * @param string $password Plaintext password
     * @param int $algorithmPassword algorithm. If not specified, the default is used.
     * @return array 0 => hash, 1 => algorithm, 2 => salt
     */
    public static function hashPassword($password, $algorithm = null)
    {
        if ($algorithm == null) {
            $algorithm = self::defaultAlgorithm();
        }

        switch ($algorithm) {
            case self::PASSWORD_PLAIN:
            case self::PASSWORD_SHA:
                throw new InvalidArgumentException('Read-only password algorithm specified');
                break;

            case self::PASSWORD_SHA_SALT:
                $salt = Security::randStr(10);
                $hash = sha1(sha1($salt . $password . $salt));
                break;

            case self::PASSWORD_SHA_SALT_5000:
                $salt = Security::randStr(10);
                $hash = $salt . $password . $salt;
                for ($i = 1; $i <= 5000; $i++) {
                    $hash = sha1($hash);
                }
                break;

            case self::PASSWORD_BCRYPT12:
                $salt = '$2y$12$';
                $salt .= Security::randStr(22, './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');

                $hash = crypt($password, $salt);
                if (strlen($hash) <= 12) {
                    throw new Exception('Bcrypt hashing failed');
                }

                // Unit tests check whether this field is set, but it's not used
                // so it's just set to a dummy value
                $salt = self::randStr(4);
                break;

            default:
                return null;
        }

        return [$hash, $algorithm, $salt];
    }


    /**
     * Checks if a given password algorithm is available
     *
     * @param int $algorithm Password algorithm
     * @return bool True if the specified algorithm is available, False otherwise
     */
    public static function checkAlgorithm($algorithm)
    {
        switch ($algorithm) {
            case self::PASSWORD_SHA:
            case self::PASSWORD_SHA_SALT:
            case self::PASSWORD_PLAIN:
            case self::PASSWORD_SHA_SALT_5000:
                return true;

            case self::PASSWORD_BCRYPT12:
                return (CRYPT_BLOWFISH == 1);
        }

        return false;
    }


    /**
     * Return the algorithm for new accounts.
     * Existing accounts will be re-crypted into this algorithm upon next login.
     *
     * @return int Password algorithm
     **/
    public static function defaultAlgorithm()
    {
        if (self::checkAlgorithm(self::PASSWORD_BCRYPT12)) {
            return self::PASSWORD_BCRYPT12;
        } else {
            return self::PASSWORD_SHA_SALT_5000;
        }
    }
}
