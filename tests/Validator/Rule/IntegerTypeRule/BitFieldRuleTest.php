<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\IntegerTypeRule;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\IntegerTypeRule\BitFieldRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(BitFieldRule::class)]
final class BitFieldRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new BitFieldRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function invalidBitFieldsDataProvider(): iterable
    {
        yield 'float' => ['struct S { float x : 3; };'];
        yield 'long double' => ['struct S { long double x : 3; };'];
        yield 'pointer' => ['struct S { int *p : 3; };'];
        yield 'structure' => ['struct S { struct T t : 3; };'];
        yield 'unnamed array' => ['struct S { int a[2] : 3; };'];
        yield 'atomic float' => ['struct S { _Atomic(float) x : 3; };'];
    }

    #[DataProvider('invalidBitFieldsDataProvider')]
    public function testBitFieldOfOtherThanIntegerTypeIsReported(string $code): void
    {
        self::assertSame([[11, 'Bit-field should have an integer type']], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validBitFieldsDataProvider(): iterable
    {
        yield 'int' => ['struct S { int x : 3; };'];
        yield 'unsigned' => ['struct S { const unsigned x : 3; };'];
        yield 'bool' => ['struct S { bool x : 1; };'];
        yield 'bit-precise integer' => ['struct S { unsigned _BitInt(7) x : 3; };'];
        yield 'char' => ['struct S { char x : 3; };'];
        yield 'enumeration' => ['struct S { enum E x : 3; };'];
        yield 'typedef name' => ['typedef double T; struct S { T x : 3; };'];
        yield 'member without a width' => ['struct S { float x; };'];
        yield 'attributes of a parenthesized declarator' => ['struct S { int (__attribute__((a)) x) : 3; };'];
        yield 'calling convention' => ['struct S { int __cdecl x : 3; };'];
    }

    #[DataProvider('validBitFieldsDataProvider')]
    public function testBitFieldOfIntegerTypeIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
