<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\IntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\IntBase;
use FFI\Parser\Node\Literal\IntLiteral\IntTypeSuffix;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(IntLiteral::class)]
final class IntLiteralTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, int, numeric-string, IntBase}>
     */
    public static function valuesDataProvider(): iterable
    {
        yield 'zero' => ['0', 0, '0', IntBase::Octal];
        yield 'zeros' => ['000', 0, '0', IntBase::Octal];
        yield 'decimal' => ['42', 42, '42', IntBase::Decimal];
        yield 'decimal with separators' => ["1'000'000", 1_000_000, '1000000', IntBase::Decimal];
        yield 'octal' => ['017', 15, '15', IntBase::Octal];
        yield 'octal with separators' => ["0'17", 15, '15', IntBase::Octal];
        yield 'hexadecimal' => ['0x1F', 31, '31', IntBase::Hexadecimal];
        yield 'hexadecimal in upper case' => ['0XABCDEF', 0xAB_CDEF, '11259375', IntBase::Hexadecimal];
        yield 'hexadecimal with separators' => ["0x1F'FF", 0x1F_FF, '8191', IntBase::Hexadecimal];
        yield 'hexadecimal with leading zeros' => ['0x0001', 1, '1', IntBase::Hexadecimal];
        yield 'binary' => ['0b1010', 10, '10', IntBase::Binary];
        yield 'binary with separators' => ["0B1'0", 2, '2', IntBase::Binary];

        yield 'decimal PHP_INT_MAX' => ['9223372036854775807', \PHP_INT_MAX, '9223372036854775807', IntBase::Decimal];
        yield 'decimal PHP_INT_MAX + 1' => ['9223372036854775808', \PHP_INT_MAX, '9223372036854775808', IntBase::Decimal];
        yield 'octal PHP_INT_MAX' => ['0777777777777777777777', \PHP_INT_MAX, '9223372036854775807', IntBase::Octal];
        yield 'octal PHP_INT_MAX + 1' => ['01000000000000000000000', \PHP_INT_MAX, '9223372036854775808', IntBase::Octal];
        yield 'hexadecimal PHP_INT_MAX' => ['0x7FFFFFFFFFFFFFFF', \PHP_INT_MAX, '9223372036854775807', IntBase::Hexadecimal];
        yield 'hexadecimal PHP_INT_MAX + 1' => ['0x8000000000000000', \PHP_INT_MAX, '9223372036854775808', IntBase::Hexadecimal];
        yield 'binary PHP_INT_MAX' => ['0b' . \str_repeat('1', 63), \PHP_INT_MAX, '9223372036854775807', IntBase::Binary];
        yield 'binary PHP_INT_MAX + 1' => ['0b1' . \str_repeat('0', 63), \PHP_INT_MAX, '9223372036854775808', IntBase::Binary];

        yield 'UINT64_MAX' => ['0xFFFFFFFFFFFFFFFF', \PHP_INT_MAX, '18446744073709551615', IntBase::Hexadecimal];
        yield 'UINT64_MAX with separators' => ["0xFFFF'FFFF'FFFF'FFFF", \PHP_INT_MAX, '18446744073709551615', IntBase::Hexadecimal];
        yield 'larger than any type of C' => ['0xFFFFFFFFFFFFFFFFFFFFFFFF', \PHP_INT_MAX, '79228162514264337593543950335', IntBase::Hexadecimal];
        yield 'UINT128_MAX' => ['0x' . \str_repeat('F', 32), \PHP_INT_MAX, '340282366920938463463374607431768211455', IntBase::Hexadecimal];
    }

    #[DataProvider('valuesDataProvider')]
    public function testValueIsParsed(string $raw, int $value, string $decimal, IntBase $base): void
    {
        $literal = IntLiteral::parse($raw, 0);

        self::assertSame($raw, $literal->raw);
        self::assertSame($value, $literal->value);
        self::assertSame($decimal, $literal->decimal);
        self::assertSame($base, $literal->base);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, IntTypeSuffix|null}>
     */
    public static function suffixesDataProvider(): iterable
    {
        yield 'none' => ['1', null];
        yield 'u' => ['1u', IntTypeSuffix::Unsigned];
        yield 'U' => ['1U', IntTypeSuffix::Unsigned];
        yield 'l' => ['1l', IntTypeSuffix::Long];
        yield 'L' => ['1L', IntTypeSuffix::Long];
        yield 'll' => ['1ll', IntTypeSuffix::LongLong];
        yield 'LL' => ['1LL', IntTypeSuffix::LongLong];
        yield 'wb' => ['1wb', IntTypeSuffix::BitPrecise];
        yield 'WB' => ['1WB', IntTypeSuffix::BitPrecise];
        yield 'ul' => ['1ul', IntTypeSuffix::UnsignedLong];
        yield 'lu' => ['1lu', IntTypeSuffix::UnsignedLong];
        yield 'ULL' => ['1ULL', IntTypeSuffix::UnsignedLongLong];
        yield 'llu' => ['1llu', IntTypeSuffix::UnsignedLongLong];
        yield 'uwb' => ['1uwb', IntTypeSuffix::UnsignedBitPrecise];
        yield 'wbU' => ['1wbU', IntTypeSuffix::UnsignedBitPrecise];
        yield 'hexadecimal digit b is not a suffix' => ['0x1b', null];
        yield 'hexadecimal digit b before wb' => ['0x1bwb', IntTypeSuffix::BitPrecise];
    }

    #[DataProvider('suffixesDataProvider')]
    public function testSuffixIsParsed(string $raw, ?IntTypeSuffix $suffix): void
    {
        self::assertSame($suffix, IntLiteral::parse($raw)->suffix);
    }

    public function testHexadecimalDigitBIsPartOfTheValue(): void
    {
        self::assertSame(0x1B, IntLiteral::parse('0x1bwb', 0)->value);
    }

    public function testOffsetIsKept(): void
    {
        self::assertSame(42, IntLiteral::parse('1', 42)->offset);
    }

    public function testBuiltConstantGetsTheExactDecimalAsSourceText(): void
    {
        self::assertSame('18446744073709551615', (new IntLiteral(\PHP_INT_MAX, '18446744073709551615'))->raw);
    }

    /**
     * @return iterable<non-empty-string, array{IntLiteral, non-empty-string}>
     */
    public static function builtConstantsDataProvider(): iterable
    {
        yield 'decimal' => [new IntLiteral(42, '42'), '42'];
        yield 'hexadecimal with a suffix' => [new IntLiteral(255, '255', IntBase::Hexadecimal, IntTypeSuffix::UnsignedLong), '0xfful'];
        yield 'octal' => [new IntLiteral(8, '8', IntBase::Octal), '010'];
        yield 'binary with a suffix' => [new IntLiteral(5, '5', IntBase::Binary, IntTypeSuffix::BitPrecise), '0b101wb'];
    }

    /**
     * A constant built without the source text gets one written in its base
     * and with its suffix.
     */
    #[DataProvider('builtConstantsDataProvider')]
    public function testBuiltConstantGetsSourceText(IntLiteral $constant, string $raw): void
    {
        self::assertSame($raw, $constant->raw);
        self::assertSame($constant->decimal, IntLiteral::parse($raw)->decimal);
    }

    /**
     * Every part of a token is decoded once, the next token is not mixed
     * with the previous one.
     */
    public function testConsecutiveConstantsAreDecodedIndependently(): void
    {
        $first = IntLiteral::parse('0x10ul');
        $second = IntLiteral::parse('010');

        self::assertSame([16, IntBase::Hexadecimal, IntTypeSuffix::UnsignedLong], [$first->value, $first->base, $first->suffix]);
        self::assertSame([8, IntBase::Octal, null], [$second->value, $second->base, $second->suffix]);
    }
}
