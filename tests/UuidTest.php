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

    const ITERATIONS_V1 = 100;
    const ITERATIONS_V4 = 10000;

    public function testUuid1()
    {
        // Less iterations because non-lazy datetime is slooowww.
        for ($i = 0; $i < self::ITERATIONS_V1; $i++) {
            $id1 = Uuid::uuid1();
            $id2 = Uuid::uuid1();

            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid1Lazy()
    {
        for ($i = 0; $i < self::ITERATIONS_V4; $i++) {
            $id1 = Uuid::uuid1(Uuid::V1_LAZY);
            $id2 = Uuid::uuid1(Uuid::V1_LAZY);

            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid1Random()
    {
        // Less iterations because non-lazy datetime is slooowww.
        for ($i = 0; $i < self::ITERATIONS_V1; $i++) {
            $id1 = Uuid::uuid1(Uuid::V1_RANDOM);
            $id2 = Uuid::uuid1(Uuid::V1_RANDOM);

            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid4()
    {
        for ($i = 0; $i < self::ITERATIONS_V4; $i++) {
            $id1 = Uuid::uuid4();
            $id2 = Uuid::uuid4();

            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid1Validate()
    {
        for ($i = 0; $i < self::ITERATIONS_V1; $i++) {
            $uuid = Uuid::uuid1();

            $this->assertFalse(Uuid::empty($uuid));
            $this->assertTrue(Uuid::valid($uuid));

            $this->assertTrue(Uuid::valid($uuid, 1));
            $this->assertFalse(Uuid::valid($uuid, 2));
            $this->assertFalse(Uuid::valid($uuid, 3));
            $this->assertFalse(Uuid::valid($uuid, 4));
            $this->assertFalse(Uuid::valid($uuid, 5));
        }
    }


    public function testUuid4Validate()
    {
        for ($i = 0; $i < self::ITERATIONS_V4; $i++) {
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

        for ($i = 0; $i < self::ITERATIONS_V1; $i++) {
            $uuid = Uuid::uuid1();
            $this->assertFalse(Uuid::empty($uuid));
        }

        for ($i = 0; $i < self::ITERATIONS_V4; $i++) {
            $uuid = Uuid::uuid4();
            $this->assertFalse(Uuid::empty($uuid));
        }
    }
}
