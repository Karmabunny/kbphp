<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2022 Karmabunny
 */

// namespace Tests;

use karmabunny\kb\AttributeTag;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * Test the Attributes tag helper.
 */
final class TagsTest extends TestCase
{

    public function testBadDocTags()
    {
        $meta = TestTag::getMetaDocTag();

        $this->assertEquals('test', $meta['name']);

        $filters = [
            'property' => 'ReflectionProperty',
            'method' => 'ReflectionMethod',
        ];

        $this->assertEquals($filters, $meta['filter']);

        try {
            TestTag::parse(TaggedBadTag::class);
            $this->fail('Expected an Error');
        }
        catch (Throwable $error) {
            if ($error instanceof AssertionFailedError) {
                throw $error;
            }

            $this->assertInstanceOf(Error::class, $error, $error->getTraceAsString());
            $this->assertStringContainsString('@test', $error->getMessage());
            $this->assertStringContainsString('cannot target constant', $error->getMessage());
            $this->assertMatchesRegularExpression('/allowed.*method/', $error->getMessage());
            $this->assertMatchesRegularExpression('/allowed.*property/', $error->getMessage());
        }
    }


    public function testDocTags()
    {
        $actual = TestTag::parse(TaggedOne::class);

        $this->assertCount(2, $actual);
        $this->assertInstanceOf(TestTag::class, $actual[0]);
        $this->assertInstanceOf(TestTag::class, $actual[1]);

        $expected = ['method one', 2, 3];
        $this->assertEquals($expected, $actual[0]->args);

        $expected = [];
        $this->assertEquals($expected, $actual[1]->args);
    }


    /**
     * @requires PHP >= 8.0
     */
    public function testBadAttributes()
    {

        try {
            TestTag::parse(TaggedBadAttribute::class);
            $this->fail('Expected an Error');
        }
        catch (Throwable $error) {
            if ($error instanceof AssertionFailedError) {
                throw $error;
            }

            $this->assertInstanceOf(Error::class, $error);
            $this->assertStringContainsString(TestTag::class, $error->getMessage());
            $this->assertStringContainsString('cannot target class', $error->getMessage());
            $this->assertMatchesRegularExpression('/allowed.*method/', $error->getMessage());
            $this->assertMatchesRegularExpression('/allowed.*property/', $error->getMessage());
        }
    }


    /**
     * @requires PHP >= 8.0
     */
    public function testAttributes()
    {
        $actual = TestTag::parse(TaggedTwo::class);

        $this->assertCount(3, $actual);
        $this->assertInstanceOf(TestTag::class, $actual[0]);
        $this->assertInstanceOf(TestTag::class, $actual[1]);
        $this->assertInstanceOf(TestTag::class, $actual[2]);

        $expected = ['arg1', 'arg2'];
        $this->assertEquals($expected, $actual[0]->args);

        $expected = [];
        $this->assertEquals($expected, $actual[1]->args);

        $expected = ['also this one'];
        $this->assertEquals($expected, $actual[2]->args);
    }


    /**
     * @requires PHP < 8.0
     */
    public function testModeFilterAttributes()
    {
        try {
            TestTag::parse(TaggedTwo::class, AttributeTag::MODE_ATTRIBUTES);
            $this->fail('Expected an Error');
        }
        catch (Throwable $error) {
            if ($error instanceof AssertionFailedError) {
                throw $error;
            }

            $this->assertInstanceOf(Error::class, $error);
            $this->assertStringContainsString('not supported', $error->getMessage());
        }
    }


    /**
     * @requires PHP >= 8.0
     */
    public function testModeFilterTags()
    {
        $tags = TestTag::parse(TaggedTwo::class, AttributeTag::MODE_DOCTAGS);
        $this->assertCount(1, $tags);

        $expected = ['also this one'];
        $this->assertEquals($expected, $tags[0]->args);
    }

}


/**
 * Test this stuff.
 *
 * I can still have **markdown** if I want.
 *
 * @attribute test method|property
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class TestTag extends AttributeTag
{
    /** @var array */
    public $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }
}


/**
 * Don't pick up @inline tags.
 *
 */
class TaggedOne
{
    /** @testskip "skip-this-one" */
    public $property1a;

    /** @test */
    public $property1b;

    /** @test "method one", 2, 3 */
    public function test1()
    {
        return true;
    }
}


class TaggedTwo
{
    /** @test "also this one" */
    #[TestTag]
    public $property2a;

    #[TaggedOne('skip this')]
    public $property2b;

    #[TestTag("arg1", "arg2")]
    public function test2()
    {
        return true;
    }
}



class TaggedBadTag
{
    /** @test */
    const BAD = 'BAD';

    /** @test "ok" */
    public function test3()
    {
        return true;
    }
}


#[TestTag]
class TaggedBadAttribute
{
    #[TestTag]
    public $property4;

    #[TestTag]
    public function test4()
    {
        return true;
    }
}
