<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

use Exception;

/**
 * This class provides a minimal implementation of UUID.
 *
 * **Important!** This will not work on 32-bit systems. You must have a 64-bit
 * proccessor and 64-bit PHP.
 *
 * Supports:
 * - UUIDv1
 * - UUIDv4
 * - UUIDv5
 *
 * UUIDs are made of 5 parts:
 * - 32b - time-low
 * - 16b - time-mid
 * - 16b - time-high-and-version
 * - 16b - clock-seq (split into 2x8b reserve + low)
 * - 48b - node
 *
 * ```
 *       low      mid  high seq  node
 * hex:  00000000-0000-0000-0000-0000000000
 * oct:  0 1 2 3  4 5  6 7  8 9  A B C D E
 * ```
 *
 * Essentially, we can stitch together whatever we need to form a base of
 * 16 bits. Then we need to just apply the variant + version. Done.
 *
 * Different versions are put together differently:
 *
 * - v1 - datetime + MAC address (collision prone because dates)
 * - v2 - datetime + MAC address + DCE security (rarely-used)
 * - v3 - md5 namespace + name (collision prone because md5)
 * - v4 - random
 * - v5 - sha1 namespace + name
 * - v6 - host ID + sequence number + time (not actually RFC'd yet)
 *
 * @package karmabunny/kb
 */
abstract class Uuid
{

    /** Use less-accurate but faster datetime generation for UUIDv1. */
    const V1_LAZY = 1;

    /** Use a random instead of mac addresses for UUIDv1. */
    const V1_RANDOM = 2;

    /** DNS addresses - host names mostly */
    const NS_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /** URLs - with all/some the bits: scheme, host, port, path */
    const NS_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    /** Object IDs - ISO OID - like 4.1.2 */
    const NS_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    /** ITU X.500 DN (in DER text output format) */
    const NS_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';


    /**
     * Get a nil UUID string.
     *
     * @return string
     */
    public static function nil(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }


    /**
     * Get a valid UUIDv1 string.
     *
     * Option flags:
     * - V1_RANDOM - Force random MAC address
     * - V1_LAZY - Use faster/less-accurate datetime
     *
     * These flags _may_ be enabled automatically if support is lacking:
     * - missing MAC address will fallback to V1_RANDOM mode
     * - missing hig-precision datetime will fallback to V1_LAZY
     *
     * @param int $options
     * @return string
     * @throws Exception Not enough entropy
     */
    public static function uuid1($options = 0): string
    {
        // 60-bit time in 100ths of nanoseconds
        $timestamp = self::getSubNanoTime($options & self::V1_LAZY);


        // Random mac addresses.
        if ($options & self::V1_RANDOM) {
            $mac = bindec(random_bytes(6));
        }
        else {
            // 48 bit mac.
            $mac = hexdec(self::getMacAddress());

            // Random fallback.
            if (!$mac) {
                $mac = bindec(random_bytes(6));
            }
        }

        $bytes = pack('NnnnNn',
            $timestamp,           // high
            $timestamp >> 32,     // mid
            $timestamp >> 48,     // low
            self::getSequence(),  // seq
            $mac >> 16,           // mac (high 32 bit)
            $mac                  // mac (low 16 bit)
        );

        $bytes = self::uuidFromBytes($bytes, 1);
        return self::format(bin2hex($bytes));
    }


    /**
     * Get a valid UUIDv4 string.
     *
     * @return string
     * @throws Exception Not enough entropy
     */
    public static function uuid4(): string
    {
        // 16 bytes, 8 bits per byte = 128 bits.
        $bytes = self::uuidFromBytes(random_bytes(16), 4);
        return self::format(bin2hex($bytes));
    }


    /**
     * Get a valid UUIDv5 string.
     *
     * @param string $namespace
     * @param string $name
     * @return string
     */
    public static function uuid5(string $namespace, string $name): string
    {
        $bytes = hex2bin(self::strip($namespace));
        $bytes = sha1($bytes . $name, true);
        $bytes = substr($bytes, 0, 16);
        $bytes = self::uuidFromBytes($bytes, 5);
        return self::format(bin2hex($bytes));
    }


    /**
     * Apply variant mask + versions to a bytes string.
     *
     * @param string $bytes 16-byte string
     * @param int $version
     * @return string also bytes, but with version + variant stuff
     */
    public static function uuidFromBytes(string $bytes, int $version): string
    {
        // Get bytes [6,7] - 16 bytes.
        $time_high = self::getOctalPair($bytes, 6);
        $time_high = pack('n', self::applyVersion($time_high, $version));

        $bytes = substr_replace($bytes, $time_high, 6, 2);

        // Get bytes [8,9] - 16 bytes.
        $clock_seq = self::getOctalPair($bytes, 8);
        $clock_seq = pack('n', self::applyVariant($clock_seq));

        $bytes = substr_replace($bytes, $clock_seq, 8, 2);

        return $bytes;
    }


    /**
     * Apply the UUID version number.
     *
     * @param int $time_high 16-bits (no version)
     * @param int $version UUID version (1-6)
     * @return int 16-bits
     */
    public static function applyVersion(int $time_high, int $version): int
    {
        // Clear the left 4 bits, shift in our version number.
        $time_high = $time_high & 0x0fff;
        $time_high |= $version << 12;

        return $time_high;
    }


    /**
     * Apply the variant-1 to the 16-bit clock sequence.
     *
     * @param int $clock_seq 16-bits (no variant)
     * @return int 16 bits
     */
    public static function applyVariant(int $clock_seq): int
    {
        // In the significant (leftmost) 4-bits.
        // Some bit-hacking voodoo to constrain it to one of: 8 9 a b
        $clock_seq = $clock_seq & 0x3fff;
        $clock_seq |= 0x8000;

        return $clock_seq;
    }


    /**
     * Is this UUID valid?
     *
     * This verifies given version + variant-1.
     *
     * @param string $uuid
     * @param int|null $version
     * @return bool
     */
    public static function valid(string $uuid, int $version = null): bool
    {
        // Strip dashes, validate length.
        $uuid = self::strip($uuid);
        if (strlen($uuid) != 32) return false;

        // Empty uids are valid! I guess?
        if ($uuid === '00000000000000000000000000000000') return true;

        // This should always pass, given our above validation.
        $uuid = @hex2bin($uuid);
        if ($uuid === false) return false;

        // Conditionally check versions.
        if ($version) {
            $actual = self::getOctalPair($uuid, 6);
            $actual = $actual >> 12;

            if ($actual !== $version) return false;
        }

        // Variant-1 is one of 4 values: 89ab.
        $variant = self::getOctalPair($uuid, 8);
        $variant = $variant >> 12;

        if ($variant < 0x8 or $variant > 0xb) return false;

        return true;
    }


    /**
     * Is this UUID nil?
     *
     * Note, true if invalid.
     *
     * @param string $uuid
     * @return bool True if the UUID is valid + empty or invalid.
     */
    public static function empty(string $uuid): bool
    {
        if (!self::valid($uuid)) return true;

        $uuid = self::strip($uuid);
        if ($uuid === '00000000000000000000000000000000') return true;
        return false;
    }


    /**
     * Does this system have a suitable MAC address.
     *
     * @return bool
     */
    public static function hasMacAddress(): bool
    {
        return self::getMacAddress() !== null;
    }


    /**
     * Does this system have support for high-precision datetimes?
     *
     * This uses the unix `date` command.
     *
     * @return bool
     */
    public static function hasHighPrecisionDatetime(): bool
    {
        return (bool) (@exec('date +%s%N') / 100);
    }


    /**
     * Get a 60-bit timestamp from 15 Oct 1582.
     *
     * A little weird, yes.
     *
     * @param bool $lazy force using microtime (faster, less accurate)
     * @return int
     */
    private static function getSubNanoTime($lazy = false)
    {
        // Unix timestamp of the epoch (negative int).
        static $epoch;
        if (!$epoch) {
            $epoch = (int) (strtotime(date('1582-10-15')) * 1000 * 1000 * 10);
        }

        // Linux nanoseconds, or microtime fallback.
        $time = 0;

        if (!$lazy) {
            $time = (int) (@exec('date +%s%N') / 100);
        }

        if (!$time) {
            $time = (int) (microtime(true) * 1000 * 1000 * 10);
        }

        return $time - $epoch;
    }


    /**
     * The system mac address.
     *
     * Most systems will have an 'ethX' or 'enpXsN' interface.
     *
     * @return string|null
     */
    private static function getMacAddress()
    {
        static $mac;
        if ($mac) return $mac;

        // Take a stab.
        $paths = glob('/sys/class/net/e*/address');
        if (empty($paths)) return null;

        $mac = @file_get_contents($paths[0]);
        if (empty($mac)) return null;

        $mac = self::strip($mac);
        if (strlen($mac) !== 12) return null;
        if (hexdec($mac) === 0) return null;

        // Got one!
        return $mac;
    }


    /**
     * Get a 'clock sequence'.
     *
     * This is a kind of fallback if the datetimes are the same.
     *
     * @return int 16-bit number.
     */
    private static function getSequence()
    {
        static $base;
        if (!$base) $base = getmypid() ?: 1;

        $base += 1;
        return $base % 0xffff;
    }


    /**
     * Get a 16-bit number from a byte string + offset.
     */
    private static function getOctalPair(string $bytes, int $offset): int
    {
        $value = unpack('n', substr($bytes, $offset, 2));
        return (int) $value[1];
    }


    /**
     * Normalize a hex string.
     *
     * Lowercase + numbers, nothing else.
     *
     * @param string $hex
     * @return string
     */
    private static function strip(string $hex): string
    {
        return preg_replace('/[^0-9a-f]/', '', strtolower($hex));
    }


    /**
     * Pretty format a UUID string.
     *
     * This doesn't validate. It doesn't do index checks.
     *
     * @param string
     * @return string
     */
    public static function format(string $uuid): string
    {
        $raw = preg_replace('/[^0-9a-f]/', '', $uuid);

        $uuid = '';
        $uuid .= substr($raw, 0, 8);
        $uuid .= '-';
        $uuid .= substr($raw, 8, 4);
        $uuid .= '-';
        $uuid .= substr($raw, 12, 4);
        $uuid .= '-';
        $uuid .= substr($raw, 16, 4);
        $uuid .= '-';
        $uuid .= substr($raw, 20, 12);

        return $uuid;
    }
}

