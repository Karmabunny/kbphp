<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\XML;
use karmabunny\kb\XMLAssertException;
use karmabunny\kb\XMLException;
use karmabunny\kb\XMLParseException;
use PHPUnit\Framework\TestCase;

/**
 * Test the XML helper utilities.
 */
final class XMLTest extends TestCase {

    public function testParse()
    {
        $xml = XML::parse('<this><works okay="hurrah!"/>wow</this>');

        $this->assertEquals('wow', (string) $xml);
        $this->assertEquals('hurrah!', (string) $xml->works['okay']);
    }


    public function testParseErrors()
    {
        try {
            XML::parse('<no>this is clearly<br> broken</no>');
            $this->fail('XML::parse() should throw.');
        }
        catch (Throwable $error) {
            $this->assertInstanceOf(XMLException::class, $error);
            $this->assertInstanceOf(XMLParseException::class, $error);
        }
    }


    public function testXpath()
    {
        $doc = XML::parse("
            <test>
                <one>thing</one>
                <two>1234</two>
                <three>12.345</three>
                <true>
                    <this/>
                    <attr false=\"true\" />
                    <number>1</number>
                    <human>yes</human>
                    <bool>true</bool>
                </true>
                <false>
                    <attr true=\"false\" />
                    <number>0</number>
                    <bool>false</bool>
                    <human>no</human>
                </false>
                <sql>\N</sql>
                <fakesql>\\n</fakesql>
            </test>
        ");

        $this->assertNotNull($doc);

        // String (default)
        $this->assertEquals('thing', XML::xpath($doc, '//one'));

        // Numbers
        $this->assertEquals(1234, XML::xpath($doc, '//two', 'int'));
        $this->assertEquals(12, XML::xpath($doc, '//three', 'int'));
        $this->assertEquals(12.345, XML::xpath($doc, '//three', 'float'));

        // Truthy
        $this->assertEquals(true, XML::xpath($doc, '//true/this', 'bool'));
        $this->assertEquals(true, XML::xpath($doc, '//true/attr', 'bool'));
        $this->assertEquals(true, XML::xpath($doc, '//true/attr/@false', 'bool'));
        $this->assertEquals(true, XML::xpath($doc, '//true/number', 'bool'));
        $this->assertEquals(true, XML::xpath($doc, '//true/bool', 'bool'));

        // Falsey
        $this->assertEquals(false, XML::xpath($doc, '//false/attr/@true', 'bool', true));
        $this->assertEquals(false, XML::xpath($doc, '//false/this', 'bool'));
        $this->assertEquals(false, XML::xpath($doc, '//false/number', 'bool'));
        $this->assertEquals(false, XML::xpath($doc, '//false/bool', 'bool'));
        $this->assertEquals(false, XML::xpath($doc, '//false/human', 'bool'));

        // Trisky SQL null looks like a line break.
        $this->assertEquals(false, XML::xpath($doc, '//sql', 'bool'));
        $this->assertEquals(true, XML::xpath($doc, '//fakesql', 'bool'));
        $this->assertEquals('\n', XML::xpath($doc, '//fakesql', 'string'));

        // Missing
        $this->assertEquals('', XML::xpath($doc, '//missing'));
        $this->assertEquals('oh no', XML::xpath($doc, '//missing', 'string', 'oh no'));

        // TODO I don't know if this is desirable.
        $this->assertEquals('whaat', XML::xpath($doc, '//missing', 'int', 'whaat'));
        $this->assertEquals(0, XML::xpath($doc, '//missing', 'int'));

        // We've already tested default false, instead test a custom default.
        $this->assertEquals(true, XML::xpath($doc, '//missing', 'bool', true));
    }


    /**
     * Test interpolations and template conditionals.
     */
    public function testFormat()
    {
        $xml = XML::format("
            <test>
                <one>{{one}}</one>
                <two>{{two}}</two>
                <another>{{two}}</another>
                <?buuut>
                    <thing attr=\"{{henlo}}\">
                        cool
                    </thing>
                </?>
                <?yeees>
                    <thing attr=\"{{bad}}\">
                        {{one}}{{two}}{{yeees}}
                    </thing>
                </?>
                <?ooh hello=\"{{henlo}}\"/>
                <?ahh hello=\"{{henlo}}\"/>
            </test>
        ", [
            'one' => 123,
            'two' => 'abc',
            'buuut' => false,
            'henlo' => 'darkness',
            'yeees' => 999,
            'ooh' => true,
            'ahh' => false,
        ]);

        $doc = XML::parse($xml);

        $this->assertNotNull($doc);
        $this->assertEquals('123', (string) $doc->xpath('//one')[0]);
        $this->assertEquals('abc', (string) $doc->xpath('//two')[0]);
        $this->assertEquals('abc', (string) $doc->xpath('//another')[0]);
        $this->assertEquals('123abc999', trim($doc->xpath('//thing')[0]));
        $this->assertEquals('{{bad}}', (string) $doc->xpath('//thing')[0]['attr']);
        $this->assertEquals('darkness', (string) $doc->xpath('//ooh')[0]['hello']);
        $this->assertEquals(0, count($doc->xpath('//ahh')));
    }


    public function testEnum()
    {
        $xml = XML::parse("
            <path>
                <to>
                    <value>1</value>
                    <value>2</value>
                    <value>3</value>
                    <value>???</value>
                </to>
            </path>
        ");

        $map = [
            '' => 'default',
            1 => 'one',
            3 => [3, 3, 3],
        ];

        $this->assertEquals('one', XML::enum($xml, '/path/to/value[1]', $map));
        $this->assertEquals('default', XML::enum($xml, '/path/to/value[2]', $map));
        $this->assertEquals([3, 3, 3], XML::enum($xml, '/path/to/value[3]', $map));
        $this->assertEquals('default', XML::enum($xml, '/path/to/value[4]', $map));
    }


    public function testHelpers()
    {
        $xml = XML::parse("
            <test hello='world' goodmorning='sunshine'>
                <nested>
                    <same>zero</same>
                </nested>
                <same>one</same>
                <same>two</same>
                <same>three</same>
                <another>four</another>
                <another>five</another>
                <same>six</same>
                seven
            </test>
        ");

        $this->assertEquals($xml->xpath('//test/same[1]')[0], XML::first($xml, 'same'));
        $this->assertEquals($xml->xpath('//test/another[1]')[0], XML::first($xml, 'another'));

        $this->assertEquals('one', XML::firstText($xml, 'same'));
        $this->assertEquals('four', XML::firstText($xml, 'another'));

        $this->assertEquals('world', XML::attr($xml, 'hello'));
        $this->assertEquals('seven', XML::text($xml));
    }


    public function testExpected()
    {
        $xml = XML::parse("
            <test hello='world'>
                <nested>
                    <same>zero</same>
                    <two>three</two>
                    <four>five</four>
                    <four>six</four>
                </nested>
                <same>one</same>
                <same>two</same>
                <same>three</same>
                <two>four</two>
                <two>four</two>
                <same>six</same>
                seven
            </test>
        ");

        $this->assertEquals($xml->xpath('//test/same')[0], XML::expectFirst($xml, 'same'));
        $this->assertEquals('one', XML::expectFirstText($xml, 'same'));

        try {
            XML::expectFirst($xml, 'doesnt_exist');
            $this->fail('Expected XMLAssertException');
        }
        catch (XMLAssertException $exception) {
            $this->assertStringContainsString('doesnt_exist', $exception->getMessage());
        }

        $actual = XML::gatherChildren($xml->nested, ['same', 'two', 'four']);
        $expected = [
            'same' => $xml->nested->same,
            'two' => $xml->nested->two,
            'four' => $xml->nested->four[0],
        ];

        $this->assertEquals($expected, $actual);

        try {
            XML::gatherChildren($xml->nested, ['ohhh', 'nooo', 'two', 'four']);
            $this->fail('Expected XMLAssertException');
        }
        catch (XMLAssertException $exception) {
            $this->assertStringContainsString('ohhh', $exception->getMessage());
            $this->assertStringContainsString('nooo', $exception->getMessage());
            $this->assertStringNotContainsString('two', $exception->getMessage());
            $this->assertStringNotContainsString('four', $exception->getMessage());
        }
    }
}