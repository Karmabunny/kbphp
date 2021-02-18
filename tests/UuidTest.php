<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

use karmabunny\kb\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * Test the UUID helper.
 */
final class UuidTest extends TestCase
{
    public function testUuid()
    {
        for ($i = 0; $i < 10000; $i++) {
            $id1 = Uuid::uuid4();
            $id2 = Uuid::uuid4();

            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testValidate()
    {
        for ($i = 0; $i < 10000; $i++) {
            $uuid = Uuid::uuid4();

            $this->assertFalse(Uuid::empty($uuid));
            $this->assertTrue(Uuid::valid($uuid));

            $this->assertFalse(Uuid::valid($uuid, 1));
            $this->assertFalse(Uuid::valid($uuid, 2));
            $this->assertFalse(Uuid::valid($uuid, 3));
            $this->assertTrue(Uuid::valid($uuid, 4));
            $this->assertFalse(Uuid::valid($uuid, 5));
        }
    }


    public function testNil()
    {
        $uuid = Uuid::nil();
        $number = (int) preg_replace('/[^0-9a-f]/', '', $uuid);

        $this->assertEquals(0, $number);

        $this->assertTrue(Uuid::empty($uuid, 4));
        $this->assertTrue(Uuid::valid($uuid, 4));
    }
}
