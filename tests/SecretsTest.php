<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Secrets;
use PHPUnit\Framework\TestCase;

const DATA = [
    'red_herring' => 'DEADBEEF',
    'id' => 'YW1pYWx3YXlzZ2VuZXJhdGluZ3BheWxvYWRzd2hlbmltaHVuZ3J5b3JhbWlhbHdheXNodW5ncnk',


    'base64_secret' => 'c2VjcmV0IG1lc3NhZ2Ugc28geW91J2xsIG5ldmVyIGd1ZXNzIG15IHBhc3N3b3Jk',
    'base64_sub_secret' => 'c2tfbGl2ZTEyMjMxMjNhc2RmYXNkZjEyMzEyMw==',

    'hex_secret' => '8b1118b376c313ed420e5133ba91307817ed52c2',
    'hex_sub_secret' => '424547494e205253412050524956415445204b45590a61736466617364666173646661736466454e44205253412050524956415445204b45590',

    'basic_auth' => 'http://username:whywouldyouusehttpforpasswords@example.com',

    'badly_named_aws_bits' => 'AKIAIOSFODNN7EXAMPLE',

    'aws_access_key' => 'AKIAIOSFODNN7EXAMPLE',
    'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',

    'stripe' => [
        'key' => 'sk_liveetcetcetc',
        'publishable' => 'pk_live-not-a-problem',

        'alternatively' => [
            'hash' => '$2y$eyyoooo$',
            'password' => 'plaintext omg',
            'cvv' => '555',
            'token' => 'lots of these',
            'list' => [
                'ghp_asdfasdf',
                'eyJ44444444444.11111111',
                'sq0csp-1234567890',
                'aws.1111token',
                'SG.1111122222333334444455.1111122222333334444455555666667777788888999'
            ],
        ],
    ],
];

/**
 * Test suite for Secrets.
 *
 * Test data is also sampled from the detect-secrets project.
 */
class SecretsTest extends TestCase
{

    public function testKeys()
    {
        $secrets = Secrets::create();
        $this->assertFalse($secrets->isSecretKey('red_herring'));
        $this->assertFalse($secrets->isSecretKey('badly_named_aws_bits'));
        $this->assertTrue($secrets->isSecretKey('aws_secret_access_key'));
    }


    public function testValues()
    {
        $secrets = Secrets::create();
        $this->assertFalse($secrets->isSecretValue(DATA['red_herring']));
        $this->assertTrue($secrets->isSecretValue(DATA['badly_named_aws_bits']));
        $this->assertFalse($secrets->isSecretValue(DATA['aws_secret_access_key']));
    }


    public function testBase64()
    {
        $secrets = Secrets::create(['base64' => true]);
        $this->assertTrue($secrets->isSecretValue(DATA['base64_secret']));
        $this->assertTrue($secrets->isSecretValue(DATA['base64_secret']));

        $secrets = Secrets::create(['base64' => false]);
        $this->assertFalse($secrets->isSecretValue(DATA['base64_secret']));
        $this->assertTrue($secrets->isSecretValue(DATA['base64_sub_secret']));
    }


    public function testHex()
    {
        $secrets = Secrets::create(['hex' => true]);
        $this->assertTrue($secrets->isSecretValue(DATA['hex_secret']));
        $this->assertTrue($secrets->isSecretValue(DATA['hex_sub_secret']));

        $secrets = Secrets::create(['hex' => false]);
        $this->assertFalse($secrets->isSecretValue(DATA['hex_secret']));
        $this->assertTrue($secrets->isSecretValue(DATA['hex_sub_secret']));
    }


    public function testCustomRuleSet()
    {
        $secrets = Secrets::create([
            'value_rules' => ['ITSASECRET-[0-9]+-ENDOFSECRET'],
        ]);

        // Good.
        $this->assertTrue($secrets->isSecretValue('ITSASECRET-12345-ENDOFSECRET'));
        $this->assertFalse($secrets->isSecretValue('ITSASECRET-abc-ENDOFSECRET'));

        // Also good (technically).
        $this->assertFalse($secrets->isSecretValue('sq0csp-abcdef'));

        // But this still works.
        $this->assertTrue($secrets->isSecretKey('password_hash'));
    }


    public function testCustomRuleSingle()
    {
        $secrets = Secrets::create();

        $secrets->addValueRule('^KBPHP.+\|.+');

        // Good.
        $this->assertTrue($secrets->isSecretValue('KBPHPabcdefg|1234567890'));

        // Meh.
        $this->assertFalse($secrets->isSecretValue('KBPHP|notquite'));

        // This still works.
        $this->assertTrue($secrets->isSecretValue('sq0csp-abcdef'));
    }


    public function testMasking()
    {
        $input = DATA;

        $secrets = Secrets::create();
        $actual = $secrets->mask($input);

        $expected = [
            'red_herring' => 'DEADBEEF',
            'id' => 'YW1pYWx3YXlzZ2VuZXJhdGluZ3BheWxvYWRzd2hlbmltaHVuZ3J5b3JhbWlhbHdheXNodW5ncnk',
            'base64_secret' => '****************',
            'base64_sub_secret' => '****************',
            'hex_secret' => '****************',
            'hex_sub_secret' => '****************',
            'basic_auth' => '****************',
            'badly_named_aws_bits' => '****************',
            'aws_access_key' => '****************',
            'aws_secret_access_key' => '****************',
            'stripe' => [
                'key' => '****************',
                'publishable' => 'pk_live-not-a-problem',
                'alternatively' => [
                    'hash' => '****************',
                    'password' => '****************',
                    'cvv' => '****************',
                    'token' => '****************',
                    'list' => [
                        '****************',
                        '****************',
                        '****************',
                        '****************',
                        '****************'
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testClean()
    {
        $input = DATA;

        $secrets = Secrets::create();
        $actual = $secrets->clean($input);

        $expected = [
            'red_herring' => 'DEADBEEF',
            'id' => 'YW1pYWx3YXlzZ2VuZXJhdGluZ3BheWxvYWRzd2hlbmltaHVuZ3J5b3JhbWlhbHdheXNodW5ncnk',
            'stripe' => [
                'publishable' => 'pk_live-not-a-problem',
                'alternatively' => [
                    'list' => [],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

}
