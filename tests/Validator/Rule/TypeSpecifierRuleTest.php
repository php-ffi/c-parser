<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\TypeSpecifierRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TypeSpecifierRule::class)]
final class TypeSpecifierRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new TypeSpecifierRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function invalidCombinationsDataProvider(): iterable
    {
        yield 'long float' => ['long float x;', '`long float`'];
        yield 'short double' => ['short double x;', '`short double`'];
        yield 'long long double' => ['long long double x;', '`long long double`'];
        yield 'unsigned double' => ['unsigned double x;', '`unsigned double`'];
        yield 'long long long' => ['long long long x;', '`long long long`'];
        yield 'short long' => ['short long x;', '`short long`'];
        yield 'short short' => ['short short x;', '`short short`'];
        yield 'signed unsigned' => ['signed unsigned x;', '`signed unsigned`'];
        yield 'unsigned unsigned' => ['unsigned unsigned x;', '`unsigned unsigned`'];
        yield 'short char' => ['short char x;', '`short char`'];
        yield 'long bool' => ['long bool x;', '`long bool`'];
        yield 'unsigned void' => ['unsigned void x;', '`unsigned void`'];
        yield 'short bit-precise integer' => ['short _BitInt(8) x;', '`short _BitInt(...)`'];
        yield 'complex bit-precise integer' => ['_Complex _BitInt(8) x;', '`_Complex _BitInt(...)`'];
        yield 'long decimal' => ['long _Decimal32 x;', '`long _Decimal32`'];
        yield 'complex decimal' => ['_Complex _Decimal64 x;', '`_Complex _Decimal64`'];
        yield 'unsigned interchange type' => ['unsigned _Float64 x;', '`unsigned _Float64`'];
        yield 'long extended type' => ['long _Float32x x;', '`long _Float32x`'];
        yield 'complex decimal interchange type' => ['_Complex _Decimal96 x;', '`_Complex _Decimal96`'];
        yield 'imaginary integer' => ['_Imaginary int x;', '`_Imaginary int`'];
        yield 'imaginary alone' => ['_Imaginary x;', '`_Imaginary`'];
        yield 'complex imaginary' => ['_Complex _Imaginary double x;', '`_Complex _Imaginary double`'];
        yield 'unsigned structure' => ['unsigned struct S x;', '`unsigned struct`'];
        yield 'long enumeration' => ['long enum E x;', '`long enum`'];
        yield 'unsigned atomic type' => ['unsigned _Atomic(int) x;', '`unsigned _Atomic(...)`'];
        yield 'long typeof' => ['long typeof(int) x;', '`long typeof(...)`'];
    }

    #[DataProvider('invalidCombinationsDataProvider')]
    public function testInvalidCombinationIsReported(string $code, string $specifiers): void
    {
        self::assertSame([[0, 'Invalid combination of type specifiers ' . $specifiers]], self::validate($code));
    }

    public function testCombinationIsCheckedWhereverTheTypeIsWritten(): void
    {
        self::assertSame(
            [
                [11, 'Invalid combination of type specifiers `long float`'],
                [43, 'Invalid combination of type specifiers `short double`'],
                [74, 'Invalid combination of type specifiers `signed bool`'],
            ],
            self::validate('struct S { long float a; }; void f(int (*)(short double)); int s = sizeof(signed bool);'),
        );
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validCombinationsDataProvider(): iterable
    {
        yield 'signed char' => ['signed char x;'];
        yield 'unsigned char' => ['unsigned char x;'];
        yield 'short' => ['short x;'];
        yield 'signed short int' => ['signed short int x;'];
        yield 'unsigned' => ['unsigned x;'];
        yield 'long' => ['long x;'];
        yield 'unsigned long long int' => ['unsigned long long int x;'];
        yield 'in any order' => ['int long unsigned long x;'];
        yield 'unsigned bit-precise integer' => ['unsigned _BitInt(8) x;'];
        yield 'long double' => ['long double x;'];
        yield 'float complex' => ['float _Complex x;'];
        yield 'long double complex' => ['long double _Complex x;'];
        yield 'double imaginary' => ['double _Imaginary x;'];
        yield 'interchange complex' => ['_Float32 _Complex x;'];
        yield 'extended imaginary' => ['_Float64x _Imaginary x;'];
        yield 'qualifiers among the modifiers' => ['unsigned const long x;'];
        yield 'complex integer' => ['_Complex int x;'];
        yield 'complex character' => ['_Complex unsigned char x;'];
        yield 'complex alone' => ['_Complex x;'];
        yield 'modifiers after a typedef name' => ['T long long x;'];
    }

    #[DataProvider('validCombinationsDataProvider')]
    public function testValidCombinationIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
