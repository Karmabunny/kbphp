<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\kb;

/**
 * This class provides a minimal implementation of UUIDv4.
 *
 * UUIDs are made of 5 parts.
 *
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
     * Get a valid UUIDv4 string.
     *
     * @return string
     */
    public static function uuid4(): string
    {
        // 16 bytes, 8 bits per byte = 128 bits.
        $bytes = self::uuidFromBytes(random_bytes(16), 4);
        return self::format(bin2hex($bytes));
    }


    /**
     * Apply variant mask + versions to a bytes string.
     *
     * @param string $bytes
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
        $clock_seq = self::getOctalPair($bytes, 6);
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
        $uuid = preg_replace('/[^0-9a-f]/', '', $uuid);
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

        $uuid = preg_replace('/[^0-9a-f]/', '', $uuid);
        if ($uuid === '00000000000000000000000000000000') return true;
        return false;
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

