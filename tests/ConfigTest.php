<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2026 Karmabunny
 */

use karmabunny\kb\Config;
use PHPUnit\Framework\TestCase;

/**
 * Test the Config helper class.
 */
final class ConfigTest extends TestCase
{

    /**
     * Test config directory path.
     *
     * @var string
     */
    const TEST_CONFIG_DIR = __DIR__ . '/config';


    /**
     * Clean up static properties before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        Config::$paths = [];
        Config::reset(true);
    }


    public function testFindWithValidName()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
            self::TEST_CONFIG_DIR . '/module',
        ];

        $paths = Config::find('settings');

        $this->assertIsArray($paths);
        $this->assertCount(2, $paths);
        $this->assertContains(self::TEST_CONFIG_DIR . '/app/settings.php', $paths);
        $this->assertContains(self::TEST_CONFIG_DIR . '/module/settings.php', $paths);
    }


    public function testFindWithInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid config file 'invalid-name!'");

        Config::find('invalid-name!');
    }


    public function testFindWithNonExistentConfig()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
        ];

        $paths = Config::find('nonexistent');

        $this->assertIsArray($paths);
        $this->assertCount(0, $paths);
    }


    public function testLoadSingleConfig()
    {
        $path = self::TEST_CONFIG_DIR . '/app/settings.php';
        $config = Config::load($path);

        $this->assertIsArray($config);
        $this->assertEquals('My Application', $config['app_name']);
        $this->assertEquals('1.0.0', $config['version']);
        $this->assertArrayHasKey('database', $config);
    }


    public function testLoadMissingFile()
    {
        $path = self::TEST_CONFIG_DIR . 'nonexistent.php';
        $config = Config::load($path);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }


    public function testGetShallowKey()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
        ];

        $value = Config::get('settings.app_name');

        $this->assertEquals('My Application', $value);
    }


    public function testGetDeepKey()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/module',
        ];

        $value = Config::get('settings.features.modules.auth.password');

        $this->assertEquals('test', $value);
    }


    public function testGetEntireConfig()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
        ];

        $config = Config::get('settings');

        $this->assertIsArray($config);
        $this->assertEquals('My Application', $config['app_name']);
        $this->assertEquals('1.0.0', $config['version']);
        $this->assertArrayHasKey('database', $config);
    }


    public function testMissingKeyRequired()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Config not found: 'settings.nonexistent'");

        Config::get('settings.nonexistent');
    }


    public function testMissingKeyNull()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
        ];

        $value = Config::get('settings.nonexistent', false);

        $this->assertNull($value);
    }


    public function testConfigBeforeMerge()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
        ];

        $config = Config::get('settings');

        // Initial app version.
        $this->assertEquals('My Application', $config['app_name']);
        $this->assertEquals('1.0.0', $config['version']);

        // Initial db config.
        $this->assertArrayHasKey('database', $config);
        $this->assertEquals('localhost', $config['database']['host']);
        $this->assertEquals(3306, $config['database']['port']);
        $this->assertEquals('app_db', $config['database']['name']);

        // Doesn't exist here.
        $this->assertArrayHasKey('lost-in-merge', $config['database']);

        // Not enabled, but has settings.
        $this->assertEquals(false, $config['merge']['enabled']);
        $this->assertEquals('value1', $config['merge']['settings1']);
        $this->assertEquals('value2', $config['merge']['settings2']);
    }


    public function testConfigMerge()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
            self::TEST_CONFIG_DIR . '/module',
        ];

        // Get the merged config.
        $config = Config::get('settings');

        // app_name should come from app/ (first path).
        $this->assertEquals('My Application', $config['app_name']);

        // version should be overridden by module/ (last path wins).
        $this->assertEquals('2.0.0', $config['version']);

        // database.host should be overridden by module/.
        $this->assertEquals('db.example.com', $config['database']['host']);
        $this->assertEquals(4417, $config['database']['port']);
        $this->assertEquals('module_db', $config['database']['name']);

        // database.ssl should exist from module/.
        $this->assertEquals('yes', $config['database']['ssl']);

        // lost-in-merge should not exist in the merged config.
        $this->assertArrayNotHasKey('lost-in-merge', $config['database']);

        // features.enabled should come from app/.
        $this->assertTrue($config['features']['enabled']);

        // features.modules.auth should be overridden by module/.
        $this->assertTrue($config['features']['modules']['auth']['enabled']);
        $this->assertEquals('test', $config['features']['modules']['auth']['password']);

        // features.modules.cache should be overridden by module/.
        $this->assertTrue($config['features']['modules']['cache']);

        // features.modules.api should exist from module/.
        $this->assertTrue($config['features']['modules']['api']);


        // merge key should exist from app/.
        $this->assertEquals(true, $config['merge']['enabled']);
        $this->assertEquals('value1', $config['merge']['settings1']);
        $this->assertEquals('value2', $config['merge']['settings2']);

        // extra key should exist from module/.
        $this->assertEquals('hiiii', $config['extra']['deep'][0]['nested1']);
        $this->assertEquals('hiiii', $config['extra']['deep'][1]['nested2']);
    }



    public function testSetOverride()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
            self::TEST_CONFIG_DIR . '/module',
        ];

        // Set an override for a shallow key.
        Config::set('settings.app_name', 'Overridden App Name');

        $value = Config::get('settings.app_name');

        $this->assertEquals('Overridden App Name', $value);
    }


    public function testSetOverrideWithDeepKey()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
            self::TEST_CONFIG_DIR . '/module',
        ];

        // Set an override for a deep key.
        Config::set('settings.database.host', 'override.example.com');

        $value = Config::get('settings.database.host');

        $this->assertEquals('override.example.com', $value);
    }


    public function testSetOverrideWithNewKey()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
            self::TEST_CONFIG_DIR . '/module',
        ];

        // Set an override for a key that doesn't exist in the config.
        Config::set('settings.new_key', 'new_value');

        $value = Config::get('settings.new_key');

        $this->assertEquals('new_value', $value);
    }


    public function testConfigCache()
    {
        Config::$paths = [
            self::TEST_CONFIG_DIR . '/app',
        ];

        // First call should load the config.
        $config1 = Config::get('settings');

        // Second call should use cache.
        $config2 = Config::get('settings');

        $this->assertEquals($config1['random'], $config2['random']);

        Config::reset();

        $config3 = Config::get('settings');
        $this->assertNotEquals($config1['random'], $config3['random']);
    }
}
