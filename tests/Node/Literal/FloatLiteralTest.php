<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\FloatLiteral;
use FFI\Parser\Node\Literal\FloatLiteral\DecFloatLiteral;
use FFI\Parser\Node\Literal\FloatLiteral\FloatParser;
use FFI\Parser\Node\Literal\FloatLiteral\FloatTypeSuffix;
use FFI\Parser\Node\Literal\FloatLiteral\HexFloatLiteral;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FloatLiteral::class)]
#[CoversClass(DecFloatLiteral::class)]
#[CoversClass(HexFloatLiteral::class)]
#[CoversClass(FloatParser::class)]
final class FloatLiteralTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, float}>
     */
    public static function decimalValuesDataProvider(): iterable
    {
        yield 'fraction' => ['1.5', 1.5];
        yield 'fraction without the whole part' => ['.5', 0.5];
        yield 'whole part without the fraction' => ['1.', 1.0];
        yield 'exponent' => ['1e10', 1e10];
        yield 'signed exponent' => ['1.5E-3', 1.5e-3];
        yield 'separators' => ["1'000.000'1", 1000.0001];
        yield 'separators in the exponent' => ["1e1'0", 1e10];
        yield 'too large' => ['1e999', \INF];
        yield 'too small' => ['1e-999', 0.0];
    }

    #[DataProvider('decimalValuesDataProvider')]
    public function testDecimalValueIsParsed(string $raw, float $value): void
    {
        $literal = DecFloatLiteral::parse($raw);

        self::assertSame($raw, $literal->raw);
        self::assertSame($value, $literal->value);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, float}>
     */
    public static function hexadecimalValuesDataProvider(): iterable
    {
        yield 'fraction' => ['0x1.8p3', 12.0];
        yield 'without the whole part' => ['0x.8p1', 1.0];
        yield 'without the fraction' => ['0x1p4', 16.0];
        yield 'negative exponent' => ['0x1p-2', 0.25];
        yield 'zero' => ['0x0p0', 0.0];
        yield 'separators' => ["0x1'0p0", 16.0];
        yield 'PHP_FLOAT_MAX' => ['0x1.fffffffffffffp1023', \PHP_FLOAT_MAX];
        yield 'smallest normal' => ['0x1p-1022', \PHP_FLOAT_MIN];
        yield 'smallest subnormal' => ['0x1p-1074', 2 ** -1074];
        yield 'too large' => ['0x1p1024', \INF];
        yield 'small mantissa with a large exponent' => ['0x0.001p1030', 2.0 ** 1018];
        yield 'long mantissa' => ['0x1.' . \str_repeat('0', 20) . '1p0', 1.0];
        yield 'dropped digits round up a value halfway' => ['0x1.00000000000008000000001p0', 1.0 + 2 ** -52];
        yield 'value halfway rounds to even' => ['0x1.00000000000008p0', 1.0];
        yield 'subnormal is rounded once' => ['0xBFFFFFFFFFFFFFp-1129', 2 ** -1074];
        yield 'half of the smallest subnormal rounds to zero' => ['0x1p-1075', 0.0];
        yield 'subnormal halfway rounds to even' => ['0x1.8p-1074', 2 ** -1073];
        yield 'huge exponent' => ['0x1p9223372036854775807', \INF];
        yield 'huge exponent with a long mantissa' => ['0x1111111111111111p9223372036854775807', \INF];
        yield 'huge negative exponent' => ['0x1.8p-9223372036854775808', 0.0];
        yield 'normal value scaled below the smallest subnormal' => [
            '0x1.00000000000010p-1022',
            \PHP_FLOAT_MIN + 2 ** -1074,
        ];
        yield 'rounding up into the next binade' => ['0x1.fffffffffffff8p0', 2.0];
        yield 'rounding up from subnormal to normal' => ['0x1.fffffffffffff8p-1023', \PHP_FLOAT_MIN];
        yield 'overflow after rounding' => ['0x1.fffffffffffff8p1023', \INF];
        yield 'just below the overflow' => ['0x1.fffffffffffff7fp1023', \PHP_FLOAT_MAX];
        yield 'mantissa of sixty bits' => ['0xf.ffffffffffffff8p0', 16.0];
        yield 'many leading zeros' => ['0x0.' . \str_repeat('0', 300) . '1p1200', 2.0 ** -4];
        yield 'separators in the exponent' => ["0x1p1'0", 1024.0];
        yield 'dot without the fraction' => ['0x1.p1', 2.0];
        yield 'upper case' => ['0X1P+4', 16.0];
    }

    #[DataProvider('hexadecimalValuesDataProvider')]
    public function testHexadecimalValueIsParsed(string $raw, float $value): void
    {
        $literal = HexFloatLiteral::parse($raw);

        self::assertSame($raw, $literal->raw);
        self::assertSame($value, $literal->value);
    }

    /**
     * @return iterable<non-empty-string, array{
     *     class-string<FloatLiteral>,
     *     non-empty-string,
     *     FloatTypeSuffix|null,
     *     int<1, max>|null,
     * }>
     */
    public static function suffixesDataProvider(): iterable
    {
        yield 'none' => [DecFloatLiteral::class, '1.5', null, null];
        yield 'f' => [DecFloatLiteral::class, '1.5f', FloatTypeSuffix::Float, null];
        yield 'F' => [DecFloatLiteral::class, '1.5F', FloatTypeSuffix::Float, null];
        yield 'l' => [DecFloatLiteral::class, '1.5l', FloatTypeSuffix::LongDouble, null];
        yield 'L' => [DecFloatLiteral::class, '1.5L', FloatTypeSuffix::LongDouble, null];
        yield 'f16' => [DecFloatLiteral::class, '1.5f16', FloatTypeSuffix::FloatN, 16];
        yield 'F32' => [DecFloatLiteral::class, '1.5F32', FloatTypeSuffix::FloatN, 32];
        yield 'f64' => [DecFloatLiteral::class, '1.5f64', FloatTypeSuffix::FloatN, 64];
        yield 'f128' => [DecFloatLiteral::class, '1.5f128', FloatTypeSuffix::FloatN, 128];
        yield 'f256' => [DecFloatLiteral::class, '1.5f256', FloatTypeSuffix::FloatN, 256];
        yield 'f32x' => [DecFloatLiteral::class, '1.5f32x', FloatTypeSuffix::FloatNx, 32];
        yield 'F64x' => [DecFloatLiteral::class, '1.5F64x', FloatTypeSuffix::FloatNx, 64];
        yield 'f128x' => [DecFloatLiteral::class, '1.5f128x', FloatTypeSuffix::FloatNx, 128];
        yield 'df' => [DecFloatLiteral::class, '1.5df', FloatTypeSuffix::Decimal32, null];
        yield 'D32' => [DecFloatLiteral::class, '1.5D32', FloatTypeSuffix::Decimal32, null];
        yield 'dd' => [DecFloatLiteral::class, '1.5dd', FloatTypeSuffix::Decimal64, null];
        yield 'd64' => [DecFloatLiteral::class, '1.5d64', FloatTypeSuffix::Decimal64, null];
        yield 'DL' => [DecFloatLiteral::class, '1.5DL', FloatTypeSuffix::Decimal128, null];
        yield 'd128' => [DecFloatLiteral::class, '1.5d128', FloatTypeSuffix::Decimal128, null];
        yield 'd96' => [DecFloatLiteral::class, '1.5d96', FloatTypeSuffix::DecimalN, 96];
        yield 'D160' => [DecFloatLiteral::class, '1.5D160', FloatTypeSuffix::DecimalN, 160];
        yield 'd64x' => [DecFloatLiteral::class, '1.5d64x', FloatTypeSuffix::DecimalNx, 64];
        yield 'D128x' => [DecFloatLiteral::class, '1.5D128x', FloatTypeSuffix::DecimalNx, 128];
        yield 'q' => [DecFloatLiteral::class, '1.5q', FloatTypeSuffix::GnuFloat128, null];
        yield 'Q' => [DecFloatLiteral::class, '1.5Q', FloatTypeSuffix::GnuFloat128, null];
        yield 'w' => [DecFloatLiteral::class, '1.5w', FloatTypeSuffix::GnuFloat80, null];
        yield 'W' => [DecFloatLiteral::class, '1.5W', FloatTypeSuffix::GnuFloat80, null];
        yield 'imaginary f16' => [DecFloatLiteral::class, '1.5f16i', FloatTypeSuffix::FloatN, 16];
        yield 'hexadecimal with f' => [HexFloatLiteral::class, '0x1p3f', FloatTypeSuffix::Float, null];
        yield 'hexadecimal with f16' => [HexFloatLiteral::class, '0x1p3f16', FloatTypeSuffix::FloatN, 16];
        yield 'hexadecimal digit f is not a suffix' => [HexFloatLiteral::class, '0x1.fp3', null, null];
        yield 'hexadecimal with q' => [HexFloatLiteral::class, '0x1p3q', FloatTypeSuffix::GnuFloat128, null];
    }

    /**
     * @param class-string<FloatLiteral> $class
     * @param int<1, max>|null $width
     */
    #[DataProvider('suffixesDataProvider')]
    public function testSuffixIsParsed(string $class, string $raw, ?FloatTypeSuffix $suffix, ?int $width): void
    {
        $literal = $class::parse($raw);

        self::assertSame([$suffix, $width], [$literal->suffix, $literal->suffixWidth]);
    }

    /**
     * @return iterable<non-empty-string, array{
     *     class-string<FloatLiteral>,
     *     non-empty-string,
     *     ?FloatTypeSuffix,
     *     bool,
     * }>
     */
    public static function imaginarySuffixesDataProvider(): iterable
    {
        yield 'none' => [DecFloatLiteral::class, '2.0', null, false];
        yield 'i' => [DecFloatLiteral::class, '2.0i', null, true];
        yield 'j after the type' => [DecFloatLiteral::class, '2.0fj', FloatTypeSuffix::Float, true];
        yield 'I before the type' => [DecFloatLiteral::class, '2.0If', FloatTypeSuffix::Float, true];
        yield 'hexadecimal' => [HexFloatLiteral::class, '0x1p1li', FloatTypeSuffix::LongDouble, true];
    }

    /**
     * @param class-string<FloatLiteral> $class
     */
    #[DataProvider('imaginarySuffixesDataProvider')]
    public function testImaginarySuffixIsParsed(
        string $class,
        string $raw,
        ?FloatTypeSuffix $suffix,
        bool $isImaginary,
    ): void {
        $constant = $class::parse($raw);

        self::assertSame(2.0, $constant->value);
        self::assertSame($suffix, $constant->suffix);
        self::assertSame($isImaginary, $constant->isImaginary);
    }

    public function testSuffixDoesNotChangeTheValue(): void
    {
        self::assertSame(12.0, HexFloatLiteral::parse('0x1.8p3f128')->value);
    }

    public function testOffsetIsKept(): void
    {
        self::assertSame(42, DecFloatLiteral::parse('1.5', 42)->offset);
    }

    /**
     * @return iterable<non-empty-string, array{
     *     class-string<FloatLiteral>,
     *     non-empty-string,
     *     non-empty-string,
     *     numeric-string,
     * }>
     */
    public static function partsDataProvider(): iterable
    {
        yield 'fraction' => [DecFloatLiteral::class, '1.5', '1.5', '0'];
        yield 'fraction without the whole part' => [DecFloatLiteral::class, '.5', '.5', '0'];
        yield 'whole part without the fraction' => [DecFloatLiteral::class, '1.', '1.', '0'];
        yield 'exponent' => [DecFloatLiteral::class, '1e10', '1', '10'];
        yield 'signed exponent with a suffix' => [DecFloatLiteral::class, '1.5E-3f', '1.5', '-3'];
        yield 'separators' => [DecFloatLiteral::class, "1'000.000'1e1'0", '1000.0001', '10'];
        yield 'exponent beyond the native integer' => [
            DecFloatLiteral::class,
            '1e99999999999999999999',
            '1',
            '99999999999999999999',
        ];
        yield 'mantissa beyond a double' => [
            DecFloatLiteral::class,
            '1.00000000000000000000000000001L',
            '1.00000000000000000000000000001',
            '0',
        ];
        yield 'hexadecimal' => [HexFloatLiteral::class, '0x1.8p3', '1.8', '3'];
        yield 'hexadecimal with a suffix' => [HexFloatLiteral::class, '0X.Fp-2f', '.F', '-2'];
        yield 'hexadecimal with digits e and f' => [HexFloatLiteral::class, '0xe.fp+1', 'e.f', '+1'];
    }

    /**
     * @param class-string<FloatLiteral> $class
     */
    #[DataProvider('partsDataProvider')]
    public function testPartsAreParsed(string $class, string $raw, string $mantissa, string $exponent): void
    {
        $literal = $class::parse($raw);

        self::assertSame($mantissa, $literal->mantissa);
        self::assertSame($exponent, $literal->exponent);
    }

    /**
     * @return iterable<non-empty-string, array{FloatLiteral, non-empty-string}>
     */
    public static function builtConstantsDataProvider(): iterable
    {
        yield 'decimal whole value' => [new DecFloatLiteral(1.0), '1.0'];
        yield 'decimal with a suffix' => [new DecFloatLiteral(1.5, suffix: FloatTypeSuffix::Float), '1.5f'];
        yield 'decimal exponent' => [new DecFloatLiteral(1.5e20), '1.5e+20'];
        yield 'decimal floating' => [new DecFloatLiteral(0.1, suffix: FloatTypeSuffix::Decimal64), '0.1dd'];
        yield 'decimal with the exact parts' => [
            new DecFloatLiteral(0.1, '0.10000000000000000001'),
            '0.10000000000000000001',
        ];
        yield 'decimal mantissa without a point' => [new DecFloatLiteral(1.0, '1'), '1e0'];
        yield 'hexadecimal' => [new HexFloatLiteral(12.0), '0xcp0'];
        yield 'hexadecimal one' => [new HexFloatLiteral(1.0), '0x1p0'];
        yield 'hexadecimal zero' => [new HexFloatLiteral(0.0), '0x0p0'];
        yield 'hexadecimal PHP_FLOAT_MAX' => [new HexFloatLiteral(\PHP_FLOAT_MAX), '0x1fffffffffffffp971'];
        yield 'hexadecimal smallest subnormal' => [new HexFloatLiteral(2 ** -1074), '0x1p-1074'];
        yield 'hexadecimal with a suffix' => [new HexFloatLiteral(0.25, suffix: FloatTypeSuffix::Float), '0x1p-2f'];
        yield 'suffix with a width' => [
            new DecFloatLiteral(1.5, suffix: FloatTypeSuffix::FloatNx, suffixWidth: 64),
            '1.5f64x',
        ];
        yield 'imaginary' => [new DecFloatLiteral(2.5, suffix: FloatTypeSuffix::Float, isImaginary: true), '2.5fi'];
    }

    #[DataProvider('builtConstantsDataProvider')]
    public function testBuiltConstantGetsSourceText(FloatLiteral $constant, string $raw): void
    {
        self::assertSame($raw, $constant->raw);
        self::assertSame($constant->value, $constant::parse($raw)->value);
    }
}
