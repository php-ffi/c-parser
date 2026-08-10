<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\IntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\BinIntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\DecIntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\HexIntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\IntParser;
use FFI\Parser\Node\Literal\IntLiteral\IntTypeSuffix;
use FFI\Parser\Node\Literal\IntLiteral\OctIntLiteral;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(IntLiteral::class)]
#[CoversClass(DecIntLiteral::class)]
#[CoversClass(HexIntLiteral::class)]
#[CoversClass(OctIntLiteral::class)]
#[CoversClass(BinIntLiteral::class)]
#[CoversClass(IntParser::class)]
final class IntLiteralTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, int, numeric-string, class-string<IntLiteral>}>
     */
    public static function valuesDataProvider(): iterable
    {
        yield 'zero' => ['0', 0, '0', OctIntLiteral::class];
        yield 'zeros' => ['000', 0, '0', OctIntLiteral::class];
        yield 'zero with a separator' => ["0'0", 0, '0', OctIntLiteral::class];
        yield 'decimal far above PHP_INT_MAX' => [
            '123456789012345678901234567890',
            \PHP_INT_MAX,
            '123456789012345678901234567890',
            DecIntLiteral::class,
        ];
        yield 'decimal' => ['42', 42, '42', DecIntLiteral::class];
        yield 'decimal with separators' => ["1'000'000", 1_000_000, '1000000', DecIntLiteral::class];
        yield 'octal' => ['017', 15, '15', OctIntLiteral::class];
        yield 'octal with separators' => ["0'17", 15, '15', OctIntLiteral::class];
        yield 'hexadecimal' => ['0x1F', 31, '31', HexIntLiteral::class];
        yield 'hexadecimal in upper case' => ['0XABCDEF', 0xAB_CDEF, '11259375', HexIntLiteral::class];
        yield 'hexadecimal with separators' => ["0x1F'FF", 0x1F_FF, '8191', HexIntLiteral::class];
        yield 'hexadecimal with leading zeros' => ['0x0001', 1, '1', HexIntLiteral::class];
        yield 'binary' => ['0b1010', 10, '10', BinIntLiteral::class];
        yield 'binary with separators' => ["0B1'0", 2, '2', BinIntLiteral::class];

        yield 'decimal PHP_INT_MAX' => [
            '9223372036854775807',
            \PHP_INT_MAX,
            '9223372036854775807',
            DecIntLiteral::class,
        ];
        yield 'decimal PHP_INT_MAX + 1' => [
            '9223372036854775808',
            \PHP_INT_MAX,
            '9223372036854775808',
            DecIntLiteral::class,
        ];
        yield 'octal PHP_INT_MAX' => [
            '0777777777777777777777',
            \PHP_INT_MAX,
            '9223372036854775807',
            OctIntLiteral::class,
        ];
        yield 'octal PHP_INT_MAX + 1' => [
            '01000000000000000000000',
            \PHP_INT_MAX,
            '9223372036854775808',
            OctIntLiteral::class,
        ];
        yield 'hexadecimal PHP_INT_MAX' => [
            '0x7FFFFFFFFFFFFFFF',
            \PHP_INT_MAX,
            '9223372036854775807',
            HexIntLiteral::class,
        ];
        yield 'hexadecimal PHP_INT_MAX + 1' => [
            '0x8000000000000000',
            \PHP_INT_MAX,
            '9223372036854775808',
            HexIntLiteral::class,
        ];
        yield 'binary PHP_INT_MAX' => [
            '0b' . \str_repeat('1', 63),
            \PHP_INT_MAX,
            '9223372036854775807',
            BinIntLiteral::class,
        ];
        yield 'binary PHP_INT_MAX + 1' => [
            '0b1' . \str_repeat('0', 63),
            \PHP_INT_MAX,
            '9223372036854775808',
            BinIntLiteral::class,
        ];

        yield 'UINT64_MAX' => ['0xFFFFFFFFFFFFFFFF', \PHP_INT_MAX, '18446744073709551615', HexIntLiteral::class];
        yield 'UINT64_MAX with separators' => [
            "0xFFFF'FFFF'FFFF'FFFF",
            \PHP_INT_MAX,
            '18446744073709551615',
            HexIntLiteral::class,
        ];
        yield 'larger than any type of C' => [
            '0xFFFFFFFFFFFFFFFFFFFFFFFF',
            \PHP_INT_MAX,
            '79228162514264337593543950335',
            HexIntLiteral::class,
        ];
        yield 'UINT128_MAX' => [
            '0x' . \str_repeat('F', 32),
            \PHP_INT_MAX,
            '340282366920938463463374607431768211455',
            HexIntLiteral::class,
        ];
    }

    /**
     * @param class-string<IntLiteral> $class
     */
    #[DataProvider('valuesDataProvider')]
    public function testValueIsParsed(string $raw, int $value, string $decimal, string $class): void
    {
        $literal = $class::parse($raw);

        self::assertSame($raw, $literal->raw);
        self::assertSame($value, $literal->value);
        self::assertSame($decimal, $literal->decimal);
    }

    /**
     * @return iterable<non-empty-string, array{class-string<IntLiteral>, non-empty-string, IntTypeSuffix|null}>
     */
    public static function suffixesDataProvider(): iterable
    {
        yield 'none' => [DecIntLiteral::class, '1', null];
        yield 'u' => [DecIntLiteral::class, '1u', IntTypeSuffix::Unsigned];
        yield 'u after the hexadecimal digit b' => [HexIntLiteral::class, '0xbu', IntTypeSuffix::Unsigned];
        yield 'uwb after the hexadecimal digit b' => [
            HexIntLiteral::class,
            '0x1bWBu',
            IntTypeSuffix::UnsignedBitPrecise,
        ];
        yield 'Ull' => [DecIntLiteral::class, '1Ull', IntTypeSuffix::UnsignedLongLong];
        yield 'lU' => [DecIntLiteral::class, '1lU', IntTypeSuffix::UnsignedLong];
        yield 'U' => [DecIntLiteral::class, '1U', IntTypeSuffix::Unsigned];
        yield 'l' => [DecIntLiteral::class, '1l', IntTypeSuffix::Long];
        yield 'L' => [DecIntLiteral::class, '1L', IntTypeSuffix::Long];
        yield 'll' => [DecIntLiteral::class, '1ll', IntTypeSuffix::LongLong];
        yield 'LL' => [DecIntLiteral::class, '1LL', IntTypeSuffix::LongLong];
        yield 'wb' => [DecIntLiteral::class, '1wb', IntTypeSuffix::BitPrecise];
        yield 'WB' => [DecIntLiteral::class, '1WB', IntTypeSuffix::BitPrecise];
        yield 'ul' => [DecIntLiteral::class, '1ul', IntTypeSuffix::UnsignedLong];
        yield 'lu' => [DecIntLiteral::class, '1lu', IntTypeSuffix::UnsignedLong];
        yield 'ULL' => [DecIntLiteral::class, '1ULL', IntTypeSuffix::UnsignedLongLong];
        yield 'llu' => [DecIntLiteral::class, '1llu', IntTypeSuffix::UnsignedLongLong];
        yield 'uwb' => [DecIntLiteral::class, '1uwb', IntTypeSuffix::UnsignedBitPrecise];
        yield 'wbU' => [DecIntLiteral::class, '1wbU', IntTypeSuffix::UnsignedBitPrecise];
        yield 'hexadecimal digit b is not a suffix' => [HexIntLiteral::class, '0x1b', null];
        yield 'hexadecimal digit b before wb' => [HexIntLiteral::class, '0x1bwb', IntTypeSuffix::BitPrecise];
    }

    /**
     * @param class-string<IntLiteral> $class
     */
    #[DataProvider('suffixesDataProvider')]
    public function testSuffixIsParsed(string $class, string $raw, ?IntTypeSuffix $suffix): void
    {
        self::assertSame($suffix, $class::parse($raw)->suffix);
    }

    public function testHexadecimalDigitBIsPartOfTheValue(): void
    {
        self::assertSame(0x1B, HexIntLiteral::parse('0x1bwb')->value);
    }

    public function testOffsetIsKept(): void
    {
        self::assertSame(42, DecIntLiteral::parse('1', 42)->offset);
    }

    /**
     * @return iterable<non-empty-string, array{IntLiteral, non-empty-string}>
     */
    public static function builtConstantsDataProvider(): iterable
    {
        yield 'decimal' => [new DecIntLiteral(42), '42'];
        yield 'decimal with a suffix' => [new DecIntLiteral(42, suffix: IntTypeSuffix::UnsignedLong), '42ul'];
        yield 'decimal beyond the native integer' => [
            new DecIntLiteral(\PHP_INT_MAX, '18446744073709551615', IntTypeSuffix::Unsigned),
            '18446744073709551615u',
        ];
        yield 'hexadecimal' => [new HexIntLiteral(255), '0xff'];
        yield 'octal' => [new OctIntLiteral(8), '010'];
        yield 'octal zero' => [new OctIntLiteral(0), '00'];
        yield 'binary with a suffix' => [new BinIntLiteral(5, suffix: IntTypeSuffix::BitPrecise), '0b101wb'];
    }

    /**
     * A constant built without the source text gets its value written in
     * its base, with its suffix.
     */
    #[DataProvider('builtConstantsDataProvider')]
    public function testBuiltConstantGetsSourceText(IntLiteral $constant, string $raw): void
    {
        self::assertSame($raw, $constant->raw);
        self::assertSame($constant->decimal, $constant::parse($raw)->decimal);
    }
}
