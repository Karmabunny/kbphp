<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

namespace karmabunny\kb;

use Attribute;
use ReflectionClass;

/**
 *
 * Repeatable attributes were considered but how to reconcile child classes
 * overriding older (or newer) versions from parent classes.
 *
 * @package karmabunny\kb
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Upgraded extends AttributeTag
{

    /** @var int[] [ name => version ] */
    public $upgrades;


    /**
     * @param string $upgrades
     */
    public function __construct(...$upgrades)
    {
        foreach ($upgrades as $upgrade) {
            [$name, $version] = static::parseUpgrade($upgrade);
            if (!$version) continue;

            $this->upgrades[$name] = (int) $version;
        }
    }


    /**
     *
     * @param mixed $target
     * @return static
     */
    public static function find($target): object
    {
        $reflect = new ReflectionClass($target);
        $tags = static::parseReflector($reflect);
        return $tags[0] ?? new static();
    }


    /**
     *
     * @param string $upgrade
     * @return array [name, verison]
     */
    protected static function parseUpgrade(string $upgrade): array
    {
        $matches = [];
        if (!preg_match('/^([^:]+):(\d+)$/', $upgrade, $matches)) {
            return [null, null];
        }

        array_shift($matches);
        return $matches;
    }


    public function compare(string $upgrade): int
    {
        [$name, $version] = static::parseUpgrade($upgrade);
        $actual = $this->upgrades[$name] ?? 0;
        return $actual <=> $version;
    }


    /**
     *
     * @param mixed $target
     * @return int
     */
    public static function check($target, string $upgrade): int
    {
        $upgraded = static::find($target);
        return $upgraded->compare($upgrade);
    }


    /**
     *
     * @param mixed $target
     * @param string $upgrade
     * @return void
     * @throws UpgradeRequiredException
     */
    public static function requires($target, string $upgrade)
    {

        if (!static::check($target, $upgrade) < 0) {
            $name = get_class($target);
            throw new UpgradeRequiredException("Requires object upgrade: {$upgrade} ({$name}) ");
        }
    }


    /**
     *
     * @param mixed $target
     * @param string $upgrade
     * @return void
     * @throws UpgradeRequiredException
     */
    public static function removed($target, string $upgrade)
    {
        if (static::check($target, $upgrade) >= 0) {
            $name = get_class($target);
            throw new UpgradeRequiredException("This functionality is removed in: {$upgrade} ({$name})");
        }
    }
}
