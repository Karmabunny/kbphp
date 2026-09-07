<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2020 Karmabunny
 */

use karmabunny\kb\Collection;
use karmabunny\kb\CastArray;
use karmabunny\kb\CastMethod;
use karmabunny\kb\CastObject;
use karmabunny\kb\TypecastTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test the typecast helper.
 */
final class TypecastTest extends TestCase
{

    public function testGoodTypes()
    {
        $thing = new TypeThing();
        $thing->update([
            'id' => '123',
            'price' => '10.00',
            'active' => 'true',
            'inactive' => 'false',
            'healthy' => 'yes',
            'unfit' => 'no',
            'happy' => 'on',
            'sad' => 'off',
            'string1' => 'unchanged',
            'string2' => 123.456,
            'array1' => 'howdy',
            'array2' => (function () { yield 'this'; yield 'works'; })(),
            'mixed' => \STDOUT,
            'untyped' => ['a', 'b', 'c'],
            'object' => [
                'name' => 'John Doe',
                'age' => '30',
            ],
            'objectList' => [
                [
                    'name' => 'John Doe',
                    'age' => '35',
                ],
                [
                    'name' => 'Jane Doe',
                    'age' => '25',
                ],
            ],
        ]);

        $this->assertSame(123, $thing->id);
        $this->assertSame(10.00, $thing->price);
        $this->assertSame(true, $thing->active);
        $this->assertSame(false, $thing->inactive);
        $this->assertSame(true, $thing->healthy);
        $this->assertSame(false, $thing->unfit);
        $this->assertSame(true, $thing->happy);
        $this->assertSame(false, $thing->sad);
        $this->assertSame('unchanged', $thing->string1);
        $this->assertSame('123.456', $thing->string2);
        $this->assertSame(\STDOUT, $thing->mixed);
        $this->assertSame(['a', 'b', 'c'], $thing->untyped);
        $this->assertSame(['howdy'], $thing->array1);
        $this->assertSame(['this', 'works'], $thing->array2);

        $this->assertInstanceOf(TypePerson::class, $thing->object);
        $this->assertSame('John Doe', $thing->object->name);
        $this->assertSame(30, $thing->object->age);

        $this->assertCount(2, $thing->objectList);

        $this->assertInstanceOf(TypePerson::class, $thing->objectList[0]);
        $this->assertSame('John Doe', $thing->objectList[0]->name);
        $this->assertSame(35, $thing->objectList[0]->age);

        $this->assertInstanceOf(TypePerson::class, $thing->objectList[1]);
        $this->assertSame('Jane Doe', $thing->objectList[1]->name);
        $this->assertSame(25, $thing->objectList[1]->age);
    }


    public function testBadTypes()
    {
        $thing = new TypeThing();
        $thing->getTypecast()->addLogger(fn($message) => fwrite(STDERR, "{$message->getMessage()} - on line {$message->getLine()}\n"));

        $thing->update([
            'id' => ['not an int'],
            'price' => ['not a float'],
            'active' => ['not a bool'],
            'inactive' => ['not a bool'],
            'healthy' => ['not a bool'],
            'unfit' => ['not a bool'],
            'happy' => ['not a bool'],
            'sad' => ['not a bool'],
            'string1' => ['not a string'],
            'string2' => ['not a string'],
            'array1' => [],
            'array2' => [],
            'mixed' => null,
            'untyped' => null,
            'object' => (object)['test' => 'object'],
            'objectList' => [
                'one' => (object)['test' => 'object'],
                'two' => new TypePerson(['name' => 'John Doe', 'age' => 30]),
                'three' => new TypeThing()
            ],
        ]);

        $this->assertSame(0, $thing->id);
        $this->assertSame(0.00, $thing->price);
        $this->assertSame(false, $thing->active);
        $this->assertSame(false, $thing->inactive);
        $this->assertSame(false, $thing->healthy);
        $this->assertSame(false, $thing->unfit);
        $this->assertSame(false, $thing->happy);
        $this->assertSame(false, $thing->sad);
        $this->assertSame('', $thing->string1);
        $this->assertSame('', $thing->string2);
        $this->assertSame([], $thing->array1);
        $this->assertSame([], $thing->array2);
        $this->assertSame(null, $thing->mixed);
        $this->assertSame(null, $thing->untyped);

        $this->assertNull($thing->object);

        $this->assertArrayNotHasKey('one', $thing->objectList);
        $this->assertArrayHasKey('two', $thing->objectList);

        $this->assertInstanceOf(TypePerson::class, $thing->objectList['two']);
        $this->assertSame('John Doe', $thing->objectList['two']->name);
        $this->assertSame(30, $thing->objectList['two']->age);

        $this->assertArrayNotHasKey('three', $thing->objectList);
    }


    public function testCustomMethod()
    {
        $thing = new TypeMethod();
        $thing->getTypecast()->addLogger(fn($message) => fwrite(STDERR, "{$message->getMessage()} - on line {$message->getLine()}\n"));

        $thing->update(['name' => 'John Doe', 'trim' => '  Hello, World!  ']);
        $this->assertSame('JOHN DOE', $thing->name);
        $this->assertSame('Hello, World!', $thing->trim);

        $thing->upper = false;
        $thing->update(['name' => 'John Doe']);
        $this->assertSame('john doe', $thing->name);
    }

    public function testUnionTypes()
    {
        $thing = new TypeUnion();

        $thing->update([
            'number' => '123',
            'string' => 456,
            'nullable' => null,
            'boolOrInt' => 'true',
            'boolIntString1' => '789',
            'boolIntString2' => 'false',
            'mixed' => 42.5,
        ]);

        $this->assertSame(123, $thing->number);
        $this->assertSame('456', $thing->string);
        $this->assertNull($thing->nullable);
        $this->assertSame(true, $thing->boolOrInt);
        $this->assertSame(789, $thing->boolIntString1);
        $this->assertSame(false, $thing->boolIntString2);
        $this->assertSame(42.5, $thing->mixed);

        // Test alternative union value assignment
        $thing->update([
            'number' => 321.5,
            'string' => 'abc',
            'nullable' => 'something',
            'boolOrInt' => 0,
            'boolIntString1' => 'abc',
            'boolIntString2' => 1,
            'mixed' => 'hello',
        ]);

        $this->assertSame(321, $thing->number);
        $this->assertSame('abc', $thing->string);
        $this->assertSame('something', $thing->nullable);
        $this->assertSame(0, $thing->boolOrInt);
        $this->assertSame('abc', $thing->boolIntString1);
        $this->assertSame(1, $thing->boolIntString2);
        $this->assertSame('hello', $thing->mixed);
    }


    public static function dataEnums(): array
    {
        return [
            'pass-through' => [
                [ 'enum1' => TypeEnumValue::BAZ, 'enum2' => TypeEnumString::BAZ, 'enum3' => TypeEnumNumber::BAZ ],
                [ 'enum1' => TypeEnumValue::BAZ, 'enum2' => TypeEnumString::BAZ, 'enum3' => TypeEnumNumber::BAZ ],
            ],
            'standard' => [
                [ 'enum1' => 'FOO', 'enum2' => 'bar', 'enum3' => 100 ],
                [ 'enum1' => TypeEnumValue::FOO, 'enum2' => TypeEnumString::BAR, 'enum3' => TypeEnumNumber::BAZ ],
            ],
            'alternate int from string' => [
                [ 'enum3' => '10' ],
                [ 'enum3' => TypeEnumNumber::BAR ],
            ],
            'invalid' => [
                [ 'enum1' => 'INVALID', 'enum3' => 1000 ],
                [ 'enum1' => null, 'enum3' => null ],
            ],
            'unit to string' => [
                ['enum4' => TypeEnumValue::FOO ],
                ['enum4' => TypeEnumValue::FOO->name ],
            ],
            'backed to string' => [
                ['enum4' => TypeEnumString::BAZ ],
                ['enum4' => TypeEnumString::BAZ->value ],
            ],
            'backed to int' => [
                ['enum4' => TypeEnumString::BAZ ],
                ['enum4' => TypeEnumString::BAZ->value ],
            ],
        ];
    }

    /**
     * @dataProvider dataEnums
     */
    public function testEnums(array $input, array $expected)
    {
        $thing = new TypeEnum();
        $thing->getTypecast()->addLogger(fn($message) => fwrite(STDERR, "{$message->getMessage()} - on line {$message->getLine()}\n"));
        $thing->update($input);

        $reflect = new ReflectionObject($thing);

        foreach ($expected as $key => $value) {
            $initialized = $reflect->getProperty($key)->isInitialized($thing);
            $this->assertTrue($initialized, "{$key} should be initialized");

            $this->assertSame($value, $thing->$key, "{$key} should be " . var_export($value, true));
        }
    }
}


class TypeThing extends Collection
{
    use TypecastTrait;

    public int $id;

    public float $price;

    public bool $active;

    public bool $inactive;

    public bool $healthy;

    public bool $unfit;

    public bool $happy;

    public bool $sad;

    public string $string1;

    public string $string2;

    public array $array1;

    public array $array2;

    public mixed $mixed;

    public $untyped;

    #[CastObject(TypePerson::class)]
    public ?Collection $object;

    #[CastArray(TypePerson::class)]
    public array $objectList;
}


class TypePerson extends Collection
{
    use TypecastTrait;

    public string $name;

    public int $age;
}


class TypeMethod extends Collection
{
    use TypecastTrait;

    #[CastMethod('toName')]
    public string $name;

    #[CastMethod('\\trim')]
    public string $trim;

    public bool $upper = true;


    public function toName(string $value): string
    {
        return $this->upper ? strtoupper($value) : strtolower($value);
    }
}


class TypeUnion extends Collection
{
    use TypecastTrait;

    public int $number;
    public string $string;
    public ?string $nullable;
    public bool|int $boolOrInt;
    public int|bool|string $boolIntString1;
    public bool|int|string $boolIntString2;
    public mixed $mixed;
}

class TypeEnum extends Collection
{
    use TypecastTrait;

    public ?TypeEnumValue $enum1;

    public TypeEnumString $enum2;

    public ?TypeEnumNumber $enum3;

    public string $enum4 = '';
}

enum TypeEnumValue
{
    case FOO;
    case BAR;
    case BAZ;
}

enum TypeEnumString: string
{
    case FOO = 'foo';
    case BAR = 'bar';
    case BAZ = 'baz';
}

enum TypeEnumNumber: int
{
    case FOO = 1;
    case BAR = 10;
    case BAZ = 100;
}
