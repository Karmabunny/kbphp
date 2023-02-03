<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\Upgraded;
use karmabunny\kb\UpgradeRequiredException;
use PHPUnit\Framework\TestCase;

/**
 * Test upgrades.
 *
 * @requires PHP >= 8.0
 */
final class UpgradesTest extends TestCase
{

    public function testUpgraded()
    {
        $thing = new UpgradedThing();

        try {
            $thing->copy();

            $this->fail('Expected UpgradeRequiredException');
        }
        catch (UpgradeRequiredException $exception) {
            $this->assertStringContainsString('Collection:2', $exception->getMessage());
            $this->assertStringContainsString('UpgradedThing', $exception->getMessage());
        }

        $blob = $thing->serialize();
        $this->assertStringNotContainsString('no_bad', $blob);
        $this->assertStringContainsString('ok_good', $blob);
    }


    public function testNotUpgraded()
    {
        $thing = new NotUpgradedThing();

        $thing->copy();
        $this->assertTrue(true);

        $blob = $thing->serialize();
        $this->assertStringContainsString('no_bad', $blob);
        $this->assertStringNotContainsString('ok_good', $blob);
    }

}


#[Upgraded('Serialize:2', 'Collection:2')]
class UpgradedThing extends Collection
{
    protected static $SERIALIZE = ReflectionProperty::IS_PRIVATE;

    private $no_bad = 'test';

    public $ok_good = 'test';
}


#[Upgraded()]
class NotUpgradedThing extends Collection
{
    protected static $SERIALIZE = ReflectionProperty::IS_PRIVATE;

    private $no_bad = 'test';

    public $ok_good = 'test';
}