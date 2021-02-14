<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Security;
use PHPUnit\Framework\TestCase;


function iter_string(string $value): Generator
{
    $len = strlen($value);
    for ($i = 0; $i < $len; $i++) {
        yield $value[$i];
    }
}


/**
* Test suite
**/
class SecurityTest extends TestCase
{

    public function testRandBytes()
    {
        $bytes = Security::randBytes(16);
        $this->assertTrue(strlen($bytes) === 16, 'Return value length');
    }

    public function testRandByte()
    {
        $byte = Security::randByte();
        $this->assertTrue(strlen($byte) === 1, 'Return value length');
    }

    public function testRandStr()
    {
        $string = Security::randStr(16);
        $this->assertTrue(strlen($string) === 16, 'Return value length');
    }


    /**
     * Data for testing random distributions
     */
    public function dataRandDistribution()
    {
        return [
            [
                [Security::class, 'randBytes'],
                [4096 * 512],
                256
            ],
            [
                [Security::class, 'randStr'],
                [4096 * 512, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
                26
            ],
            [
                [Security::class, 'randStr'],
                [4096 * 512, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'],
                52
            ],
            [
                [Security::class, 'randStr'],
                [4096 * 512, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'],
                62
            ],
        ];
    }

    /**
     * @dataProvider dataRandDistribution
     *
     * @param callable $func Function to generate random strings
     * @param array $args Function arguments
     * @param int $num_unique Expected number of unique values returned from $func
     */
    public function testRandDistribution($func, array $args, $num_unique)
    {
        $bytes = call_user_func_array($func, $args);

        $dists = [];
        foreach (iter_string($bytes) as $b) {
            $b = ord($b);
            if (isset($dists[$b])) {
                $dists[$b]++;
            } else {
                $dists[$b] = 1;
            }
        }
        $this->assertCount($num_unique, $dists);

        $avg = strlen($bytes) / $num_unique;
        $thresh = $avg * 0.1;
        foreach ($dists as $b => $count) {
            $diff = abs($count - $avg);
            $this->assertLessThan($thresh, $diff, "Byte {$b} count {$count} expected {$avg} (+/- {$thresh})");
        }
    }


    public function dataAlgorithms()
    {
        return [
            [Security::PASSWORD_DEFAULT],
            [Security::PASSWORD_BCRYPT],
            [Security::PASSWORD_SHA_SALT],
            [Security::PASSWORD_SHA_SALT_5000],
            [Security::PASSWORD_BCRYPT12],
        ];
    }


    /**
    * Does hash creation match hash checking?
    * @dataProvider dataAlgorithms
    **/
    public function testHashMatchCheck($alg)
    {
        list ($a, $b, $c) = Security::hashPassword('Match', $alg);
        $result = Security::doPasswordCheck($a, $b, $c, 'Match');
        $this->assertTrue($result);
        $this->assertTrue($alg == $b);

        list ($a, $b, $c) = Security::hashPassword('Match', $alg);
        $result = Security::doPasswordCheck($a, $b, $c, 'Do not match');
        $this->assertFalse($result);
        $this->assertTrue($alg == $b);
    }


    /**
    * Does two creations create different hashes? (hashes with salts)
    * @dataProvider dataAlgorithms
    **/
    public function testHashWithSalts($alg)
    {
        list ($a1, $b1, $c1) = Security::hashPassword('Match', $alg);
        list ($a2, $b2, $c2) = Security::hashPassword('Match', $alg);
        $this->assertTrue($b1 == $b2);
        $this->assertTrue($alg == $b1);
        $this->assertTrue($a1 != $a2);
        $this->assertTrue($c1 != $c2);
    }

}
