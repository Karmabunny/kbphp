<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Doc;
use karmabunny\kb\Errors\DocAssertException;
use karmabunny\kb\Errors\DocException;
use karmabunny\kb\Errors\DocParseException;
use PHPUnit\Framework\TestCase;

/**
 * Test the XML helper utilities.
 */
final class XMLTest extends TestCase {

    public function testParse()
    {
        $xml = Doc::parse('<this><works okay="hurrah!"/>wow</this>');

        $this->assertEquals('wow', $xml->textContent);
        $this->assertEquals('hurrah!', $xml->getElementsByTagName('works')[0]->getAttribute('okay'));
    }


    public function testParseErrors()
    {
        try {
            Doc::parse('<no>this is clearly<br> broken</no>');
            $this->fail('XML::parse() should throw.');
        }
        catch (Throwable $error) {
            $this->assertInstanceOf(DocException::class, $error);
            $this->assertInstanceOf(DocParseException::class, $error);
        }
    }


    public function testXpath()
    {
        $doc = Doc::parse("
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

        // String
        $this->assertEquals('thing', Doc::xpath($doc, '//one', 'string'));

        // Numbers
        $this->assertEquals(1234, Doc::xpath($doc, '//two', 'int'));
        $this->assertEquals(12, Doc::xpath($doc, '//three', 'int'));
        $this->assertEquals(12.345, Doc::xpath($doc, '//three', 'float'));

        // Truthy
        $this->assertEquals(true, Doc::xpath($doc, '//true/this', 'bool'));
        $this->assertEquals(true, Doc::xpath($doc, '//true/attr', 'bool'));
        $this->assertEquals(true, Doc::xpath($doc, '//true/attr/@false', 'bool'));
        $this->assertEquals(true, Doc::xpath($doc, '//true/number', 'bool'));
        $this->assertEquals(true, Doc::xpath($doc, '//true/bool', 'bool'));

        // Falsey
        $this->assertEquals(false, Doc::xpath($doc, '//false/attr/@true', 'bool', true));
        $this->assertEquals(false, Doc::xpath($doc, '//false/this', 'bool'));
        $this->assertEquals(false, Doc::xpath($doc, '//false/number', 'bool'));
        $this->assertEquals(false, Doc::xpath($doc, '//false/bool', 'bool'));
        $this->assertEquals(false, Doc::xpath($doc, '//false/human', 'bool'));

        // Trisky SQL null looks like a line break.
        $this->assertEquals(false, Doc::xpath($doc, '//sql', 'bool'));
        $this->assertEquals(true, Doc::xpath($doc, '//fakesql', 'bool'));
        $this->assertEquals('\n', Doc::xpath($doc, '//fakesql', 'string'));

        // Missing
        $this->assertNull(Doc::xpath($doc, '//missing', 'element'));
        $this->assertEquals('oh no', Doc::xpath($doc, '//missing', 'string') ?: 'oh no');

        $this->assertEquals('whaat', Doc::xpath($doc, '//missing', 'int') ?: 'whaat');
        $this->assertEquals(0, Doc::xpath($doc, '//missing', 'int'));

        // We've already tested default false, instead test a custom default.
        $this->assertEquals(true, Doc::xpath($doc, '//missing', 'bool') ?: true);
    }


    /**
     * Test interpolations and template conditionals.
     */
    public function testFormat()
    {
        $doc = Doc::format("
            <test>
                <one>{{one}}</one>
                <two>{{two}}</two>
                <another>{{two}}</another>
                <?if buuut ?>
                    <thing attr='{{henlo}}'>
                        cool
                    </thing>
                <?endif ?>
                <?if yeees ?>
                    <thing attr='{{bad}}'>
                        {{one}}{{two}}{{yeees}}
                    </thing>
                <?endif ?>
                <?if ooh hello='{{henlo}}' ?>
                <?if ahh hello='{{henlo}}' ?>
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

        $expected = simplexml_import_dom($doc);

        $this->assertNotNull($doc);
        $this->assertEquals('123', (string) $expected->xpath('//one')[0]);
        $this->assertEquals('abc', (string) $expected->xpath('//two')[0]);
        $this->assertEquals('abc', (string) $expected->xpath('//another')[0]);
        $this->assertEquals('123abc999', trim($expected->xpath('//thing')[0]));
        $this->assertEquals('{{bad}}', (string) $expected->xpath('//thing')[0]['attr']);
        $this->assertEquals('darkness', (string) $expected->xpath('//ooh')[0]['hello']);
        $this->assertEquals(0, count($expected->xpath('//ahh')));
    }


    public function testEnum()
    {
        $xml = Doc::parse("
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

        $this->assertEquals('one', Doc::enum($xml, '/path/to/value[1]', $map));
        $this->assertEquals('default', Doc::enum($xml, '/path/to/value[2]', $map));
        $this->assertEquals([3, 3, 3], Doc::enum($xml, '/path/to/value[3]', $map));
        $this->assertEquals('default', Doc::enum($xml, '/path/to/value[4]', $map));
    }


    public function testHelpers()
    {
        $xml = Doc::parse("
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

        $this->assertInstanceOf(DOMElement::class, Doc::first($xml, 'same'));
        $this->assertInstanceOf(DOMElement::class, Doc::first($xml, 'another'));
        $this->assertNotEquals(Doc::first($xml, 'same'), Doc::first($xml, 'another'));

        $this->assertEquals('one', Doc::firstText($xml, 'same'));
        $this->assertEquals('four', Doc::firstText($xml, 'another'));

        $this->assertEquals('world', Doc::attr($xml, 'hello'));
        $this->assertEquals(trim($xml->textContent), Doc::text($xml));
    }


    public function testExpected()
    {
        $xml = Doc::parse("
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

        $expected = simplexml_import_dom($xml);

        $this->assertInstanceOf(DOMElement::class, Doc::expectFirst($xml, 'same'));
        $this->assertEquals(dom_import_simplexml($expected->same[0]), Doc::expectFirst($xml, 'same'));
        $this->assertEquals('one', Doc::expectFirstText($xml, 'same'));

        try {
            Doc::expectFirst($xml, 'doesnt_exist');
            $this->fail('Expected XMLAssertException');
        }
        catch (DocAssertException $exception) {
            $this->assertStringContainsString('doesnt_exist', $exception->getMessage());
        }

        $actual = Doc::gatherChildren(Doc::first($xml, 'nested'), ['same', 'two', 'four']);
        $expected = [
            'same' => dom_import_simplexml($expected->nested->same[0]),
            'two' => dom_import_simplexml($expected->nested->two[0]),
            'four' => dom_import_simplexml($expected->nested->four[0]),
        ];

        $this->assertEquals($expected, $actual);

        try {
            Doc::gatherChildren(Doc::first($xml, 'nested'), ['ohhh', 'nooo', 'two', 'four']);
            $this->fail('Expected XMLAssertException');
        }
        catch (DocAssertException $exception) {
            $this->assertStringContainsString('ohhh', $exception->getMessage());
            $this->assertStringContainsString('nooo', $exception->getMessage());
            $this->assertStringNotContainsString('two', $exception->getMessage());
            $this->assertStringNotContainsString('four', $exception->getMessage());
        }
    }


    public function testString()
    {
        $xml = Doc::parse("
            <hi>
                <oh dear = 'true'>this</oh>
                is
            a
        mess</hi>
        ");

        $actual = Doc::toString(Doc::xpath($xml, '//hi/oh', 'element'));
        $expected = '<oh dear="true">this</oh>';
        $this->assertEquals($expected, $actual);
    }
}