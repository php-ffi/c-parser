<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\FloatLiteral;
use FFI\Parser\Node\Literal\FloatLiteral\FloatBase;
use FFI\Parser\Node\Literal\FloatLiteral\FloatParser;
use FFI\Parser\Node\Literal\FloatLiteral\FloatTypeSuffix;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FloatLiteral::class)]
#[CoversClass(FloatParser::class)]
final class FloatLiteralTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, float}>
     */
    public static function valuesDataProvider(): iterable
    {
        yield 'fraction' => ['1.5', 1.5];
        yield 'fraction without the whole part' => ['.5', 0.5];
        yield 'whole part without the fraction' => ['1.', 1.0];
        yield 'exponent' => ['1e10', 1e10];
        yield 'signed exponent' => ['1.5E-3', 1.5e-3];
        yield 'separators' => ["1'000.000'1", 1000.0001];
        yield 'too large' => ['1e999', \INF];
        yield 'too small' => ['1e-999', 0.0];

        yield 'hexadecimal' => ['0x1.8p3', 12.0];
        yield 'hexadecimal without the whole part' => ['0x.8p1', 1.0];
        yield 'hexadecimal without the fraction' => ['0x1p4', 16.0];
        yield 'hexadecimal with a negative exponent' => ['0x1p-2', 0.25];
        yield 'hexadecimal zero' => ['0x0p0', 0.0];
        yield 'hexadecimal with separators' => ["0x1'0p0", 16.0];
        yield 'PHP_FLOAT_MAX' => ['0x1.fffffffffffffp1023', \PHP_FLOAT_MAX];
        yield 'smallest normal' => ['0x1p-1022', \PHP_FLOAT_MIN];
        yield 'smallest subnormal' => ['0x1p-1074', 2 ** -1074];
        yield 'too large hexadecimal' => ['0x1p1024', \INF];
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
        yield 'decimal separators in the exponent' => ["1e1'0", 1e10];
    }

    #[DataProvider('valuesDataProvider')]
    public function testValueIsParsed(string $raw, float $value): void
    {
        $literal = FloatLiteral::parse($raw, 0);

        self::assertSame($raw, $literal->raw);
        self::assertSame($value, $literal->value);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, FloatTypeSuffix|null}>
     */
    public static function suffixesDataProvider(): iterable
    {
        yield 'none' => ['1.5', null];
        yield 'f' => ['1.5f', FloatTypeSuffix::Float];
        yield 'F' => ['1.5F', FloatTypeSuffix::Float];
        yield 'l' => ['1.5l', FloatTypeSuffix::LongDouble];
        yield 'L' => ['1.5L', FloatTypeSuffix::LongDouble];
        yield 'f16' => ['1.5f16', FloatTypeSuffix::Float16];
        yield 'F32' => ['1.5F32', FloatTypeSuffix::Float32];
        yield 'f64' => ['1.5f64', FloatTypeSuffix::Float64];
        yield 'f128' => ['1.5f128', FloatTypeSuffix::Float128];
        yield 'f32x' => ['1.5f32x', FloatTypeSuffix::Float32x];
        yield 'f64x' => ['1.5f64x', FloatTypeSuffix::Float64x];
        yield 'f128x' => ['1.5f128x', FloatTypeSuffix::Float128x];
        yield 'df' => ['1.5df', FloatTypeSuffix::Decimal32];
        yield 'D32' => ['1.5D32', FloatTypeSuffix::Decimal32];
        yield 'dd' => ['1.5dd', FloatTypeSuffix::Decimal64];
        yield 'd64' => ['1.5d64', FloatTypeSuffix::Decimal64];
        yield 'DL' => ['1.5DL', FloatTypeSuffix::Decimal128];
        yield 'd128' => ['1.5d128', FloatTypeSuffix::Decimal128];
        yield 'd64x' => ['1.5d64x', FloatTypeSuffix::Decimal64x];
        yield 'D128x' => ['1.5D128x', FloatTypeSuffix::Decimal128x];
        yield 'hexadecimal with f' => ['0x1p3f', FloatTypeSuffix::Float];
        yield 'hexadecimal digit f is not a suffix' => ['0x1.fp3', null];
    }

    #[DataProvider('suffixesDataProvider')]
    public function testSuffixIsParsed(string $raw, ?FloatTypeSuffix $suffix): void
    {
        self::assertSame($suffix, FloatLiteral::parse($raw, 0)->suffix);
    }

    public function testSuffixDoesNotChangeTheValue(): void
    {
        self::assertSame(12.0, FloatLiteral::parse('0x1.8p3f128', 0)->value);
    }

    public function testOffsetIsKept(): void
    {
        self::assertSame(42, FloatLiteral::parse('1.5', 42)->offset);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string, numeric-string, FloatBase}>
     */
    public static function partsDataProvider(): iterable
    {
        yield 'fraction' => ['1.5', '1.5', '0', FloatBase::Decimal];
        yield 'fraction without the whole part' => ['.5', '.5', '0', FloatBase::Decimal];
        yield 'whole part without the fraction' => ['1.', '1.', '0', FloatBase::Decimal];
        yield 'exponent' => ['1e10', '1', '10', FloatBase::Decimal];
        yield 'signed exponent with a suffix' => ['1.5E-3f', '1.5', '-3', FloatBase::Decimal];
        yield 'separators' => ["1'000.000'1e1'0", '1000.0001', '10', FloatBase::Decimal];
        yield 'exponent beyond the native integer' => [
            '1e99999999999999999999',
            '1',
            '99999999999999999999',
            FloatBase::Decimal,
        ];
        yield 'mantissa beyond a double' => [
            '1.00000000000000000000000000001L',
            '1.00000000000000000000000000001',
            '0',
            FloatBase::Decimal,
        ];
        yield 'hexadecimal' => ['0x1.8p3', '1.8', '3', FloatBase::Hexadecimal];
        yield 'hexadecimal with a suffix' => ['0X.Fp-2f', '.F', '-2', FloatBase::Hexadecimal];
        yield 'hexadecimal with digits e and f' => ['0xe.fp+1', 'e.f', '+1', FloatBase::Hexadecimal];
    }

    /**
     * The mantissa and the exponent keep the value exactly, whatever its
     * precision.
     */
    #[DataProvider('partsDataProvider')]
    public function testPartsAreParsed(string $raw, string $mantissa, string $exponent, FloatBase $base): void
    {
        $literal = FloatLiteral::parse($raw);

        self::assertSame($mantissa, $literal->mantissa);
        self::assertSame($exponent, $literal->exponent);
        self::assertSame($base, $literal->base);
    }

    /**
     * @return iterable<non-empty-string, array{FloatLiteral, non-empty-string}>
     */
    public static function builtConstantsDataProvider(): iterable
    {
        yield 'whole value' => [new FloatLiteral(1.0, '1'), '1e0'];
        yield 'fraction with a suffix' => [new FloatLiteral(1.5, '1.5', suffix: FloatTypeSuffix::Float), '1.5e0f'];
        yield 'exponent' => [new FloatLiteral(1.5e20, '1.5', '20'), '1.5e20'];
        yield 'decimal floating' => [new FloatLiteral(0.1, '0.1', suffix: FloatTypeSuffix::Decimal64), '0.1e0dd'];
        yield 'hexadecimal' => [new FloatLiteral(12.0, '1.8', '3', FloatBase::Hexadecimal), '0x1.8p3'];
    }

    /**
     * A constant built without the source text gets one written out of its
     * mantissa and exponent. The exponent is always there, so that the text
     * is not read as an integer constant.
     */
    #[DataProvider('builtConstantsDataProvider')]
    public function testBuiltConstantGetsSourceText(FloatLiteral $constant, string $raw): void
    {
        self::assertSame($raw, $constant->raw);
        self::assertSame($constant->value, FloatLiteral::parse($raw)->value);
    }
}
