<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

namespace karmabunny\kb;

use Redis;
use RedisException;
use RuntimeException;


/**
 * A thin wrapper around phpredis.
 *
 * See also the php-redis documentation:
 *     https://github.com/phpredis/phpredis
 *
 * Most methods have been documented here.
 * There's more, obviously. We'll get there.
 *
 * @method int append(string $key, string $value)
 * @method bool bgSave()
 * @method int dbSize()
 * @method int decr(string $key, int $by = 1)
 * @method int del(...$keys)
 * @method string|false dump(string $key)
 * @method int exists(string $key)
 * @method bool expire(string $key, int $seconds)
 * @method bool expireAt(string $key, int $timestamp)
 * @method bool flushAll(bool $async = null)
 * @method bool flushDb(bool $async = null)
 * @method mixed get(string $key)
 * @method int hDel(string $key, ...$memberKeys)
 * @method bool hExists(string $key, string $memberKey)
 * @method mixed hGet(string $key, string $memberKey)
 * @method array hGetAll(string $key)
 * @method array hKeys(string $key)
 * @method int hLen(string $key)
 * @method int hSet(string $key, string $memberKey, $value)
 * @method int hSetNx(string $key, string $memberKey, $value)
 * @method int hVals(string $key)
 * @method int incr(string $key, int $by = 1)
 * @method array info()
 * @method array keys(string $pattern)
 * @method mixed lGet(string $key, int $index)
 * @method int lInsert(string $key, $position, $pivot, $value)
 * @method int lLen(string $key)
 * @method mixed lPop(string $key)
 * @method int lPush(string $key, ...$values)
 * @method array lRange(string $key, int $start, int $end)
 * @method int|false lRem(string $key, $value, int $count)
 * @method bool lSet(string $key, int $index, $value)
 * @method array mGet(...$keys)
 * @method bool mSet(...$pairs)
 * @method void publish(string $channel, string $message)
 * @method mixed pubSub(string $keyword, $argument = null)
 * @method void restore(string $key, int $ttl, string $dump)
 * @method mixed rPop(string $key)
 * @method int rPush(string $key, ...$values)
 * @method bool save()
 * @method bool set(string $key, $value)
 * @method bool setEx(string $key, int $ttl, $value)
 * @method bool setNx(string $key, $value)
 * @method void subscribe(array $channels, callable $cb)
 * @method int sAdd(string $key, ...$values)
 * @method int sCard(string $key)
 * @method array sDiff(...$keys)
 * @method int sDiffStore(string $dst, ...$keys)
 * @method array sInter(...$keys)
 * @method int sInterStore(string $dst, ...$keys)
 * @method bool sIsMember(string $key, $value)
 * @method array sMembers(string $key)
 * @method array|false sPop(string $key, int $count = 1)
 * @method array|false sRandMember(string $key, int $count = 1)
 * @method long sRem(string $key, $member)
 * @method array sUnion(...$keys)
 * @method int sUnionStore(string $dst, ...$keys)
 * @method int ttl(string $key)
 * @method mixed type(string $key)
 *
 * @package karmabunny\kb
 */
class Rdb
{

    const FILTER_PREFIX = [
        'keys',
    ];

    private static $r = [];

    private $redis = null;

    private $prefix = null;


    /**
     * Configure and connect to to a server.
     *
     * Config properties:
     * - endpoint: 'ip_or_address:port' or just 'address' to imply port 6379
     * - prefix: optional key prefix (null)
     * - timeout: optional, default 1
     * - read_timeout: optional, default 15
     *
     * @param array $config [ endpoint, ?prefix, ?timeout, ?read_timeout ]
     * @throws RuntimeException
     */
    public function __construct(array $config)
    {
        if (!class_exists('Redis')) {
            throw new RuntimeException("Extension 'php-redis' not loaded");
        }

        list($host, $port) = self::getHostPort($config['endpoint']);

        // Normalize stuff.
        $config['endpoint'] = "{$host}:{$port}";
        $config['prefix'] = @$config['prefix'] ?: null;
        $config['timeout'] = @$config['timeout'] ?: 1;
        $config['read_timeout'] = @$config['read_timeout'] ?: 15;

        $key = serialize($config);

        $this->prefix = $config['prefix'];
        $this->redis = @self::$r[$key] ?: null;

        // If the connection doesn't already exist - let's make one.
        if ($this->redis === null) {
            $this->redis = new Redis();

            // 1 sec timeout with 100ms delay between attempts
            $result = $this->redis->connect($host, $port, $config['timeout'], NULL, 100);

            if ($result === false) {
                throw new RedisException('Unable to connect to Redis server');
            }

            // When in development use a prefix to avoid clashes
            // Production will have dedicated database so not required
            if ($config['prefix'] !== null) {
                $this->redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
            }

            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

            self::$r[$key] = $this->redis;
        }
    }


    /**
     * Get the host and port to use for Redis connections
     *
     * @return array [ host , port ]
     */
    public static function getHostPort(string $endpoint)
    {
        $parts = explode(':', $endpoint, 2);
        if (count($parts) == 1) $parts[] = '6379';
        return $parts;
    }


    /**
     * Call a method on the underlying Redis object
     *
     * @example
     *    rdb::set('testing:key', 'my-test-value');
     *
     * @example
     *    rdb::get('testing:key');
     *
     * @example
     *    rdb::hSet('testing:hash', 'key', 'value');
     */
    public function __call(string $name, array $arguments)
    {
        $result = call_user_func_array([$this->redis, $name], $arguments);

        if (in_array($name, self::FILTER_PREFIX)) {
            $result = $this->filterKeysPrefix($result);
        }

        return $result;
    }


    /**
     * Filter out the development-only prefix used on all keys
     *
     * This is so the KEYS and similar methods work correctly
     *
     * @param array $keys Key names with prefixes
     * @return array Key names without prefixes
     */
    public function filterKeysPrefix(array $keys)
    {
        if (!empty($this->prefix)) {
            foreach ($keys as &$k) {
                $k = preg_replace("/^{$this->prefix}/", '', $k);
            }
        }
        return $keys;
    }


    /**
     * Execute a function and cache it's results
     *
     * It's important that the function throws exceptions, rather than returning null
     * or other forms of error handling or the caching won't work properly
     *
     * @throws Exception If the underlying function throws an exception
     * @param string $key Key to store results in
     * @param int $ttl Expiry time in seconds if a key is stored
     * @param callable $func Function to execute
     * @param array ...$args Function arguments
     * @return mixed Function result
     */
    public function exec($key, $ttl, $func, ...$args)
    {
        $value = $this->get($key);

        if ($value === false) {
            $value = $func(...$args);
            $this->setEx($key, $ttl, $value);
        }

        return $value;
    }

}
