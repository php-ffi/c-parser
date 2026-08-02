<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

use FFI\Parser\Node\Literal\FloatLiteral\FloatParser\FloatParserResult;

/**
 * Parses the source text of a floating constant.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 */
final class FloatParser
{
    /**
     * Matches a constant without digit separators: the number and the
     * suffix.
     */
    private const PATTERN = '/^(.+?)([fF](?:128x?|64x?|32x?|16)|[dD](?:128x?|64x?|32)|df|DF|dd|DD|dl|DL|[fFlL])?$/';

    /**
     * Matches a hexadecimal number: the whole part, the fraction and the
     * binary exponent.
     */
    private const HEXADECIMAL_PATTERN = '/^0[xX]([0-9A-Fa-f]*)\.?([0-9A-Fa-f]*)[pP]([+-]?[0-9]+)$/';

    /**
     * Hexadecimal digits that always fit in a 64-bit integer (60 bits). The
     * mantissa of a double has 53 bits, the rest is enough to round the
     * value correctly once the dropped digits are folded into the lowest
     * bit.
     */
    private const MAX_MANTISSA_DIGITS = 15;

    /**
     * Bound of the written exponent. Beyond it the value is either infinite
     * or zero whatever the mantissa is, and the bound keeps the arithmetic
     * on the exponent from overflowing.
     */
    private const MAX_EXPONENT = 1 << 40;

    /**
     * The binary exponent of the smallest subnormal double, `0x1p-1074`.
     */
    private const MIN_SUBNORMAL_EXPONENT = -1074;

    /**
     * The binary exponent of the smallest normal double, `0x1p-1022`.
     */
    private const MIN_NORMAL_EXPONENT = -1022;

    /**
     * A power of two that is a normal double, so scaling by it is exact.
     */
    private const SCALE_STEP = 1000;

    /**
     * @param non-empty-string $raw text of a `T_FLOAT_LITERAL` token
     */
    public static function parse(string $raw): FloatParserResult
    {
        \preg_match(self::PATTERN, \str_replace("'", '', $raw), $parts);

        // A token of the lexer always matches, the defaults only keep the
        // types of the parts exact.
        [, $number, $suffix] = $parts + [1 => '0', 2 => ''];

        return new FloatParserResult(
            value: self::parseValue($number),
            suffix: self::parseSuffix($suffix),
        );
    }

    private static function parseValue(string $number): float
    {
        if (\stripos($number, '0x') === 0) {
            return self::parseHexadecimal($number);
        }

        return (float) $number;
    }

    /**
     * PHP does not understand hexadecimal floating numbers, so the value is
     * the mantissa scaled by the binary exponent.
     */
    private static function parseHexadecimal(string $number): float
    {
        \preg_match(self::HEXADECIMAL_PATTERN, $number, $parts);

        [, $whole, $fraction, $exponent] = $parts + [1 => '', 2 => '', 3 => '0'];

        $exponent = \max(-self::MAX_EXPONENT, \min(self::MAX_EXPONENT, (int) $exponent));
        $exponent -= 4 * \strlen($fraction);
        $digits = \ltrim($whole . $fraction, '0');

        if ($digits === '') {
            return 0.0;
        }

        $mantissa = \intval(\substr($digits, 0, self::MAX_MANTISSA_DIGITS), 16);

        if (\strlen($digits) > self::MAX_MANTISSA_DIGITS) {
            $dropped = \substr($digits, self::MAX_MANTISSA_DIGITS);
            $exponent += 4 * \strlen($dropped);

            // The value is correctly rounded only if the dropped digits are
            // not lost entirely: whether they are zero decides the rounding
            // of a value that lies exactly halfway
            if (\ltrim($dropped, '0') !== '') {
                $mantissa |= 1;
            }
        }

        return self::toFloat($mantissa, $exponent);
    }

    /**
     * Rounds `mantissa * 2^exponent` to the nearest double, ties to even.
     *
     * A normal value is rounded once, by the conversion of the mantissa. A
     * subnormal one has fewer significant bits than the conversion keeps, so
     * the mantissa is rounded to them first, otherwise it would be rounded
     * twice.
     */
    private static function toFloat(int $mantissa, int $exponent): float
    {
        $bits = \strlen(\decbin($mantissa));

        if ($exponent + $bits - 1 < self::MIN_NORMAL_EXPONENT) {
            $shift = self::MIN_SUBNORMAL_EXPONENT - $exponent;

            // Less than a half of the smallest subnormal
            if ($shift > $bits) {
                return 0.0;
            }

            if ($shift > 0) {
                $mantissa = self::shiftRoundingToEven($mantissa, $shift);
                $exponent = self::MIN_SUBNORMAL_EXPONENT;
            }
        }

        return self::scale((float) $mantissa, $exponent);
    }

    /**
     * Shifts the integer to the right, rounding the dropped bits half to
     * even.
     */
    private static function shiftRoundingToEven(int $value, int $shift): int
    {
        $kept = $value >> $shift;
        $rest = $value & ((1 << $shift) - 1);
        $half = 1 << ($shift - 1);

        if ($rest > $half || ($rest === $half && ($kept & 1) === 1)) {
            return $kept + 1;
        }

        return $kept;
    }

    /**
     * Multiplies the value by the power of two. The power alone underflows
     * below `0x1p-1074` while the product does not (`0x1.00000000000010p-1022`
     * is scaled by `2^-1074`), so a small power is taken in two steps. A large
     * one overflows to {@see \INF} along with the product.
     */
    private static function scale(float $value, int $exponent): float
    {
        if ($exponent < -self::SCALE_STEP) {
            $value *= 2 ** -self::SCALE_STEP;
            $exponent += self::SCALE_STEP;
        }

        return $value * 2 ** $exponent;
    }

    private static function parseSuffix(string $suffix): ?FloatTypeSuffix
    {
        return match (\strtolower($suffix)) {
            'f' => FloatTypeSuffix::Float,
            'l' => FloatTypeSuffix::LongDouble,
            'f16' => FloatTypeSuffix::Float16,
            'f32' => FloatTypeSuffix::Float32,
            'f64' => FloatTypeSuffix::Float64,
            'f128' => FloatTypeSuffix::Float128,
            'f32x' => FloatTypeSuffix::Float32x,
            'f64x' => FloatTypeSuffix::Float64x,
            'f128x' => FloatTypeSuffix::Float128x,
            'df', 'd32' => FloatTypeSuffix::Decimal32,
            'dd', 'd64' => FloatTypeSuffix::Decimal64,
            'dl', 'd128' => FloatTypeSuffix::Decimal128,
            'd64x' => FloatTypeSuffix::Decimal64x,
            'd128x' => FloatTypeSuffix::Decimal128x,
            default => null,
        };
    }
}
