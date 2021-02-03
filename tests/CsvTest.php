<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\CsvImport;
use karmabunny\kb\CsvExport;
use PHPUnit\Framework\TestCase;

/**
 * Test the CsvImport + CsvExport classes.
 */
final class CsvTest extends TestCase
{
   public function testExportImport()
   {
        $original = [
            [
                'id' => 111,
                'name' => 'Big ol\' name',
                'description' => "This has \n breaks\nohhh noooo\n\n",
                'empty' => null
            ],
            [
                'id' => 222,
                'name' => 'Second',
                // no description!
                'empty' => 0,
            ],
            [
                'id' => 333,
                'name' => 'json thing',
                'description' => json_encode([
                    'ah' => 'gross',
                    'just' => ['a', 'big', 'mess', 123, true, null]
                ]),
                'empty' => false,
            ],
            [
                'id' => 444,
                'name' => 'Last one',
                'description' => "This has quotes \" and other stuff \t see?",
                'empty' => '',
            ]
        ];

        // Pbfft. Uh yeah I guess this is shorter than writing it out again.
        $expected = $original;
        array_walk_recursive($expected, function(&$value) {
            $value = (string) $value;
        });
        $expected[1]['description'] = null;

        // Export it all.
        $export = new CsvExport();
        $export->addAll($original);
        $csv = $export->build();

        // Import it again.
        $import = CsvImport::fromString($csv);
        $actual = iterator_to_array($import);

        $this->assertEquals($expected, $actual);
   }


//    public function testExcelImport()
//    {
//    }


//    public function testSproutImport()
//    {
//    }


//    public function testSqlImport()
//    {
//    }
}
