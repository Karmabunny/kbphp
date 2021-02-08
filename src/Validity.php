<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use InvalidArgumentException;


/**
 * Validation class.
 *
 * All of its methods should give useful errors by throwing a {@see ValidityException}.
 * Used with the {@see RulesValidator} class.
 *
 * @package karmabunny\kb
 */
abstract class Validity
{

    /**
     * Checks the length of a string is within an allowed range
     *
     * @example
     *    $valid->check('name', 'length', 1, 100)
     *
     * @param string $val The value
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @throws ValidityException If item is too short or too long
     */
    public static function length($val, $min, $max = PHP_INT_MAX)
    {
        $len = mb_strlen($val);
        if ($len < $min) {
            throw new ValidityException("Shorter than minimum allowed length of {$min}");
        }
        if ($len > $max) {
            throw new ValidityException("Longer than maximum allowed length of {$max}");
        }
    }


    /**
     * Validate email, commonly used characters only
     *
     * @example
     *    $valid->check('email', 'email')
     *
     * @param string email address
     * @throws ValidityException
     */
    public static function email($val)
    {
        $regex = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD';

        if (!preg_match($regex, $val)) {
            throw new ValidityException('Invalid email address');
        }
    }


    /**
     * Validate password by length, type of characters
     *
     * @example
     *    $valid->check('password', 'password')
     *
     * @param string $val Password to validate
     * @throws ValidityException
     */
    public static function password($val)
    {
        $errs = [];

        if (mb_strlen($val) < 8) {
            $errs[] = "must be at least 8 characters long";
        }

        if (!preg_match('/[a-z]/', $val)) {
            $errs[] = "must contain a lowercase letter";
        }

        if (!preg_match('/[A-Z]/', $val)) {
            $errs[] = "must contain an uppercase letter";
        }

        if (!preg_match('/[0-9]/', $val)) {
            $errs[] = "must contain a number";
        }

        if (count($errs) > 0) {
            throw new ValidityException(ucfirst(implode(', ', $errs)));
        }
    }


    /**
     * Checks if a phone number is valid.
     *
     * @example
     *    $valid->check('mobile', 'phone', 10)
     *
     * @param string $val Phone number
     * @param int $min_digits Minimum number of digits required in phone number.
     *        This can be less than 8 for fields which allow short numbers like 000 or 13 11 66
     * @throws ValidityException
     */
    public static function phone($val, $min_digits = 8)
    {
        $min_digits = (int) $min_digits;
        if ($min_digits <= 0) $min_digits = 8;

        // Allow international numbers starting with + and country code, e.g. +61 for Australia
        $clean = preg_replace('/^\+[0-9]+ */', '', $val);

        // Allow area code in parentheses, e.g. in Australia (08) or Mexico (01 55)
        $clean = preg_replace('/^\(([0-9]+(?: [0-9]+)*)\)/', '$1', $clean);

        // Allow all kinds of different digit separation:
        // space (AU), dash - (US), dot . and slash / (crazy Belgians)
        if (preg_match('#[^\- 0-9/\.]#', $clean)) {
            if (preg_match('#[\+\(\)]#', $clean)) {
                throw new ValidityException("Invalid format");
            }
            throw new ValidityException("Contains invalid characters");
        }

        // Check length meets the minimum requirement
        $len = strlen(preg_replace('/[^0-9]/', '', $val));
        if ($len < $min_digits) {
            throw new ValidityException("Must contain at least {$min_digits} digits");
        }
        if ($len > 15) {
            throw new ValidityException("Cannot contain more than 15 digits");
        }
    }


    /**
     * Checks if a value is a positive integer
     *
     * @example
     *    $valid->check('region_id', 'positiveInt')
     *
     * @param string $val Value to check
     * @throws ValidityException
     */
    public static function positiveInt($val)
    {
        if (preg_match('/[^0-9]/', $val)) {
            throw new ValidityException("Value must be a whole number that is greater than zero");
        }

        $int = (int) $val;
        if ($int <= 0) {
            throw new ValidityException("Value must be greater than zero");
        }
    }


    /**
     * Checks whether a string is made up of the kinds of characters that make up prose
     *
     * Allowed: letters, numbers, space, punctuation
     * Allowed punctuation:
     *    ' " / ! ? @ # $ % & ( ) - : ; . ,
     *
     * @example
     *    $valid->check('name', 'proseText')
     *
     * @param string $str
     * @throws ValidityException
     */
    public static function proseText($str)
    {
        // pL = letters, pN = numbers
        if (preg_match('/[^-\pL\pN \'"\/!?@#$%&():;.,]/u', (string) $str)) {
            throw new ValidityException('Non prose characters found');
        }
    }


    /**
     * Checks if a value is a date in MySQL format (YYYY-MM-DD)
     *
     * @example
     *    $valid->check('date_published', 'dateMySQL')
     *
     * @param string $val Value to check
     * @throws ValidityException
     */
    public static function dateMySQL($val)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $val, $matches)) {
            throw new ValidityException('Invalid date format');
        }

        if ($matches[1] < 1900 or $matches[1] > 2100) {
            throw new ValidityException('Year is outside of range of 1900 to 2100');
        }

        if ($matches[2] < 1 or $matches[2] > 12) {
            throw new ValidityException('Month is outside of range of 1 to 12');
        }

        if ($matches[3] < 1 or $matches[3] > 31) {
            throw new ValidityException('Day is outside of range of 1 to 31');
        }
    }


    /**
     * Checks if a value is a time in MySQL format (HH:MM:SS)
     *
     * @example
     *    $valid->check('event_time', 'timeMySQL')
     *
     * @param string $val Value to check
     * @throws ValidityException
     */
    public static function timeMySQL($val)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $val, $matches)) {
            throw new ValidityException('Invalid time format');
        }

        if ($matches[1] < 0 or $matches[1] > 23) {
            throw new ValidityException('Hour is outside of range of 0 to 23');
        }

        if ($matches[2] < 0 or $matches[2] > 59) {
            throw new ValidityException('Minute is outside of range of 0 to 59');
        }

        if ($matches[3] < 0 or $matches[3] > 59) {
            throw new ValidityException('Second is outside of range of 0 to 59');
        }
    }


    /**
     * Checks if a value is a datetime in MySQL format (YYYY-MM-DD HH:MM:SS)
     *
     * @example
     *    $valid->check('start_date', 'datetimeMySQL')
     *
     * @param string $val Value to check
     * @throws ValidityException
     */
    public static function datetimeMySQL($val)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})$/', $val, $matches)) {
            throw new ValidityException('Invalid datedate format');
        }

        self::dateMySQL($matches[1]);
        self::timeMySQL($matches[2]);
    }


    /**
     * At least one value must be specified (e.g. one of email/phone/mobile)
     *
     * @example
     *    $valid->multipleCheck(['email', 'phone'], 'oneRequired')
     *
     * @param array $vals Values to check
     * @throws ValidityException
     */
    public static function oneRequired(array $vals)
    {
        foreach ($vals as $v) {
            if (is_array($v) and count($v) > 0) {
                return;
            } else if ($v != '') {
                return;
            }
        }

        throw new ValidityException("At least one of these must be provided");
    }


    /**
     * All field values must match (e.g. password1 and password2 must match)
     *
     * @example
     *    $valid->multipleCheck(['password1', 'password2'], 'allMatch')
     *
     * @param array $vals Values to check
     * @throws ValidityException
     */
    public static function allMatch(array $vals)
    {
        $unique = array_unique($vals);
        if (count($unique) > 1) {
            throw new ValidityException("Provided values do not match");
        }
    }



    /**
     * All field values must be unique (e.g. home phone and work phone cannot be the same)
     *
     * @example
     *    $valid->multipleCheck(['home_phone', 'work_phone'], 'allUnique')
     *
     * @param array $vals Values to check
     * @throws ValidityException
     */
    public static function allUnique(array $vals)
    {
        $unique = array_unique($vals);
        if (count($unique) != count($vals)) {
            throw new ValidityException("Provided values must not be the same");
        }
    }


    /**
     * Checks a value is one of the allowed values
     *
     * @example
     *    $valid->check('vowel', 'inArray', ['a', 'e', 'i', 'o', 'u'])
     *
     * @param string $val
     * @param array $allowed
     * @throws ValidityException
     */
    public static function inArray($val, array $allowed)
    {
        if (!in_array($val, $allowed)) {
            throw new ValidityException('Invalid value');
        }
    }


    /**
     * Checks each value of an array is one of the allowed values
     *
     * @example
     *    $_POST['vowels'] = ['a', 'i']
     *    $valid->check('vowel', 'allInArray', ['a', 'e', 'i', 'o', 'u'])
     *
     * @param string $val
     * @param array $allowed
     * @throws ValidityException
     */
    public static function allInArray(array $val, array $allowed)
    {
        if (count(array_diff($val, $allowed)) > 0) {
            throw new ValidityException('Invalid value');
        }
    }


    /**
     * Checks that a value is numeric (integral or decimal)
     *
     * @example
     *    $valid->check('cost', 'numeric')
     *
     * @param string $val
     * @throws ValidityException
     */
    public static function numeric($val)
    {
        if (!is_numeric($val)) {
            throw new ValidityException('Value must be a number');
        }
    }


    /**
     * Checks that a value is binary; either a '1' or a '0'.
     *
     * @example
     *    $valid->check('active', 'binary')
     *
     * @param string $val
     * @throws ValidityException
     */
    public static function binary($val)
    {
        if ($val !== '1' and $val !== 1 and $val !== '0' and $val !== 0) {
            throw new ValidityException('Value must be a "1" or "0"');
        }
    }


    /**
     * Checks that a value is numeric (integral or decimal) and within a given inclusive range
     *
     * @example
     *    $valid->check('cost', 'range', 0, 5000)
     *
     * @param string $val
     * @param number $min The minimum the value may be
     * @param number $max The maximum the value may be
     * @throws ValidityException
     */
    public static function range($val, $min, $max)
    {
        static::numeric($val);

        if ($val < $min or $val > $max) {
            throw new ValidityException("Value must be no less than {$min} and no greater than {$max}");
        }
    }


    /**
     * Checks that a date range is valid.
     *
     * @example
     *    $valid->multipleCheck(['date_start', 'date_end'], 'Validity::dateRange', '1999-01-01', '2099-01-01')
     *
     * @param array $vals The values to check; there must be exactly two with the 'start' field name occuring first in the array
     * @param string $min (optional) A date string (compatible with strtotime) for the minimum of the date range.
     * @param string $max (optional) A date string (compatible with strtotime) for the maximum of the date range.
     * @param bool $enforce_ordering (optional) Ensures that the start date is less than or equal to the end date. On by default.
     */
    public static function dateRange(array $vals, $min = null, $max = null, $enforce_ordering = true)
    {
        if (count($vals) != 2) {
            throw new InvalidArgumentException('Incorrect number of fields. A date range must only contain two dates: a start and an end date.');
        }

        list ($date_start, $date_end) = $vals;

        static::dateMySQL($date_start);
        static::dateMySQL($date_end);

        $ts_start = strtotime($date_start);
        $ts_end = strtotime($date_end);

        // Ideally we'd just switch the values around but that isn't possible
        if ($enforce_ordering and $ts_start > $ts_end) {
            throw new ValidityException("The start date, {$date_start}, cannot be later than the end date {$date_end}");
        }

        if ($min) {
            $ts_min = strtotime($min);

            if ($ts_start < $ts_min) {
                throw new ValidityException("The start of this date range is outside the minimum of {$min}");
            }
        }

        if ($max) {
            $ts_max = strtotime($max);

            if ($ts_end > $ts_max) {
                throw new ValidityException("The end of this date range is outside the maximum of {$max}");
            }
        }
    }


    /**
     * Checks that a value matches a regular expression
     * @param string $val value
     * @param string $pattern Regex pattern for preg_match. Consider starting with /^ and ending with $/
     * @return void
     * @throws ValidityException If the value doesn't match the pattern
     */
    public static function regex($val, $pattern)
    {
        if (!preg_match($pattern, $val)) {
            throw new ValidityException('Incorrect format');
        }
    }


    /**
     * Checks that a value is a valid IPv4 address
     * @param string $val value
     * @return void
     * @throws ValidityException If the value isn't a valid IPv4 address
     */
    public static function ipv4Addr($val)
    {
        if (!preg_match('/^[0-9]+(?:\.[0-9]+){3}$/', $val)) {
            throw new ValidityException('Invalid IP address');
        }

        $parts = explode('.', $val);
        foreach ($parts as $part) {
            $part = (int) $part;
            if ($part > 255) throw new ValidityException('Invalid IP address');
        }
    }


    /**
     * Checks that a value is a valid IPv4 CIDR block
     * @param string $val value
     * @return void
     * @throws ValidityException If the value isn't a valid IPv4 CIDR block
     */
    public static function ipv4Cidr($val)
    {
        if (strpos($val, '/') === false) {
            throw new ValidityException('Invalid CIDR block');
        }

        list($ip, $mask) = explode('/', $val, 2);
        self::ipv4Addr($ip);

        if (!preg_match('/^[0-9]{1,2}$/', $mask)) {
            throw new ValidityException('Invalid network mask');
        }
        $mask = (int) $mask;
        if ($mask > 32) {
            throw new ValidityException('Invalid network mask');
        }
    }


    /**
     * Checks that a value is a valid IPv4 address or CIDR block
     * @param string $val value
     * @return void
     * @throws ValidityException If the value isn't a valid IPv4 address or CIDR block
     */
    public static function ipv4AddrOrCidr($val)
    {
        if (strpos($val, '/') === false) {
            self::ipv4Addr($val);
        } else {
            self::ipv4Cidr($val);
        }
    }

}
