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
    const ITERATIONS_V5 = 10000;

    public function testUuid1()
    {
        // Less iterations because non-lazy datetime is slooowww.
        for ($i = 0; $i < self::ITERATIONS_V1; $i++) {
            $id1 = Uuid::uuid1();
            $id2 = Uuid::uuid1();

            $this->assertEquals(36, strlen($id1));
            $this->assertEquals(36, strlen($id2));
            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid1Lazy()
    {
        for ($i = 0; $i < self::ITERATIONS_V4; $i++) {
            $id1 = Uuid::uuid1(Uuid::V1_LAZY);
            $id2 = Uuid::uuid1(Uuid::V1_LAZY);

            $this->assertEquals(36, strlen($id1));
            $this->assertEquals(36, strlen($id2));
            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid1Random()
    {
        // Less iterations because non-lazy datetime is slooowww.
        for ($i = 0; $i < self::ITERATIONS_V1; $i++) {
            $id1 = Uuid::uuid1(Uuid::V1_RANDOM);
            $id2 = Uuid::uuid1(Uuid::V1_RANDOM);

            $this->assertEquals(36, strlen($id1));
            $this->assertEquals(36, strlen($id2));
            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid4()
    {
        for ($i = 0; $i < self::ITERATIONS_V4; $i++) {
            $id1 = Uuid::uuid4();
            $id2 = Uuid::uuid4();

            $this->assertEquals(36, strlen($id1));
            $this->assertEquals(36, strlen($id2));
            $this->assertNotEquals($id1, $id2);
        }
    }


    public function testUuid5()
    {
        // Because uuid5 is deterministic, we can make sure our algorithm
        // is correct against the RFC implementation.

        $actual = Uuid::uuid5(Uuid::NS_URL, "https://example.com");
        $expected = '4fd35a71-71ef-5a55-a9d9-aa75c889a6d0';
        $this->assertEquals($expected, $actual);

        $ns = Uuid::uuid4();

        for ($i = 0; $i < self::ITERATIONS_V5; $i++) {
            $id1 = Uuid::uuid5($ns, 'one');
            $id2 = Uuid::uuid5($ns, 'two');

            $this->assertEquals(36, strlen($id1));
            $this->assertEquals(36, strlen($id2));
            $this->assertNotEquals($id1, $id2);

            $id3 = Uuid::uuid5($ns, 'one');

            $this->assertEquals(36, strlen($id3));
            $this->assertEquals($id1, $id3);

            $name = bin2hex(random_bytes(8));
            $dns = Uuid::uuid5(Uuid::NS_DNS, $name);
            $url = Uuid::uuid5(Uuid::NS_URL, $name);
            $oid = Uuid::uuid5(Uuid::NS_OID, $name);
            $x500 = Uuid::uuid5(Uuid::NS_X500, $name);

            $this->assertNotContains($dns, [$url, $oid, $x500]);
            $this->assertNotContains($url, [$dns, $oid, $x500]);
            $this->assertNotContains($oid, [$url, $dns, $x500]);
            $this->assertNotContains($x500, [$url, $oid, $dns]);
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


    public function testUuid5Validate()
    {
        $ns = Uuid::uuid4();

        for ($i = 0; $i < self::ITERATIONS_V5; $i++) {
            $uuid = Uuid::uuid5($ns, bin2hex(random_bytes(8)));

            $this->assertFalse(Uuid::empty($uuid));
            $this->assertTrue(Uuid::valid($uuid));

            $this->assertFalse(Uuid::valid($uuid, 1));
            $this->assertFalse(Uuid::valid($uuid, 2));
            $this->assertFalse(Uuid::valid($uuid, 3));
            $this->assertFalse(Uuid::valid($uuid, 4));
            $this->assertTrue(Uuid::valid($uuid, 5));
        }
    }


    public function testUuidValidVariants()
    {
        foreach (range(0, self::ITERATIONS_V4) as $i) {
            $uuid = Uuid::uuid4();

            $this->assertTrue(Uuid::valid($uuid));
            $this->assertTrue(Uuid::valid($uuid, 4));
            $this->assertTrue(Uuid::valid($uuid, 4, 1));

            $this->assertFalse(Uuid::valid($uuid, 4, 0));
            $this->assertFalse(Uuid::valid($uuid, 4, 2));
            $this->assertFalse(Uuid::valid($uuid, 4, 3));

            $variant0 = 'e5b8ed34-d6a1-11ec-5987-0242ac120002';
            $this->assertFalse(Uuid::valid($variant0));
            $this->assertTrue(Uuid::valid($variant0, null, null));
            $this->assertTrue(Uuid::valid($variant0, null, 0));
            $this->assertFalse(Uuid::valid($variant0, null, 1));
            $this->assertFalse(Uuid::valid($variant0, null, 2));

            $variant1 = 'e5b8ed34-d6a1-11ec-b987-0242ac120002';
            $this->assertTrue(Uuid::valid($variant1));
            $this->assertTrue(Uuid::valid($variant1, null, null));
            $this->assertTrue(Uuid::valid($variant1, null, 1));
            $this->assertFalse(Uuid::valid($variant1, null, 0));
            $this->assertFalse(Uuid::valid($variant1, null, 2));

            $variant2 = 'e5b8ed34-d6a1-11ec-d987-0242ac120002';
            $this->assertFalse(Uuid::valid($variant2));
            $this->assertTrue(Uuid::valid($variant2, null, null));
            $this->assertTrue(Uuid::valid($variant2, null, 2));
            $this->assertFalse(Uuid::valid($variant2, null, 0));
            $this->assertFalse(Uuid::valid($variant2, null, 1));

            $reserved = 'e5b8ed34-d6a1-11ec-f987-0242ac120002';
            $this->assertFalse(Uuid::valid($reserved));
            $this->assertFalse(Uuid::valid($reserved, null, 0));
            $this->assertFalse(Uuid::valid($reserved, null, 1));
            $this->assertFalse(Uuid::valid($reserved, null, 2));
            $this->assertFalse(Uuid::valid($reserved, null, 3));
            $this->assertFalse(Uuid::valid($reserved, null, null));

            // NIL is pretty much always valid. It has no variants.
            $nil = Uuid::NIL;
            $this->assertTrue(Uuid::valid($nil, null, null, true));
            $this->assertFalse(Uuid::valid($nil, null, null, false));
            $this->assertTrue(Uuid::valid($nil, null, 0, true));
            $this->assertTrue(Uuid::valid($nil, null, 1, true));
            $this->assertTrue(Uuid::valid($nil, null, 2, true));
            $this->assertTrue(Uuid::valid($nil, null, 3, true));
        }
    }


    public function testNil()
    {
        $uuid = Uuid::nil();
        $number = (int) preg_replace('/[^0-9a-f]/', '', $uuid);

        $this->assertEquals(0, $number);

        $this->assertTrue(Uuid::empty($uuid, 4));
        $this->assertTrue(Uuid::valid($uuid, 4));

        $this->assertFalse(Uuid::valid($uuid, 4, null, false));

        for ($i = 0; $i < self::ITERATIONS_V1; $i++) {
            $uuid = Uuid::uuid1();
            $this->assertFalse(Uuid::empty($uuid));
        }

        for ($i = 0; $i < self::ITERATIONS_V4; $i++) {
            $uuid = Uuid::uuid4();
            $this->assertFalse(Uuid::empty($uuid));
        }

        $ns = Uuid::uuid4();

        for ($i = 0; $i < self::ITERATIONS_V5; $i++) {
            $uuid = Uuid::uuid5($ns, bin2hex(random_bytes(8)));
            $this->assertFalse(Uuid::empty($uuid));
        }
    }
}
