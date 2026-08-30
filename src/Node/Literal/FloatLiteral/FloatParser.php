<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

use FFI\Parser\Node\Literal\FloatLiteral\FloatParser\FloatParserResult;

/**
 * Parses the source text of a floating constant.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class FloatParser
{
    /**
     * Matches a decimal constant without digit separators: the mantissa, the
     * optional exponent and the suffix.
     */
    private const DECIMAL_PATTERN = '/^([0-9]*\.?[0-9]*)(?:[eE]([+-]?[0-9]+))?(.*)$/';

    /**
     * Matches a hexadecimal constant without digit separators: the mantissa,
     * the binary exponent and the suffix.
     */
    private const HEXADECIMAL_PATTERN = '/^0[xX]([0-9A-Fa-f]*\.?[0-9A-Fa-f]*)[pP]([+-]?[0-9]+)(.*)$/';

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
     * The exponent of the step a small power of two is taken in, `0x1p-1000`
     * is a normal double
     */
    private const SCALE_STEP = 1000;

    /**
     * Bits a hexadecimal digit stands for.
     */
    private const BITS_PER_HEX_DIGIT = 4;

    /**
     * @param non-empty-string $raw text of a floating constant
     */
    public static function parse(string $raw): FloatParserResult
    {
        $literal = \str_replace("'", '', $raw);

        if (\stripos($literal, '0x') === 0) {
            return self::parseHexadecimal($literal);
        }

        return self::parseDecimal($literal);
    }

    private static function parseDecimal(string $literal): FloatParserResult
    {
        // A token of the lexer always matches, an absent exponent is an
        // empty group
        \preg_match(self::DECIMAL_PATTERN, $literal, $parts);

        /** @var array{string, non-empty-string, numeric-string|'', string} $parts */
        $exponent = $parts[2] === '' ? '0' : $parts[2];

        return new FloatParserResult(
            value: (float) ($parts[1] . 'e' . $exponent),
            mantissa: $parts[1],
            exponent: $exponent,
            suffix: self::parseSuffix($parts[3]),
            isImaginary: self::isImaginary($parts[3]),
        );
    }

    private static function parseHexadecimal(string $literal): FloatParserResult
    {
        // A token of the lexer always matches
        \preg_match(self::HEXADECIMAL_PATTERN, $literal, $parts);

        /** @var array{string, non-empty-string, numeric-string, string} $parts */
        return new FloatParserResult(
            value: self::evaluateHexadecimal($parts[1], $parts[2]),
            mantissa: $parts[1],
            exponent: $parts[2],
            suffix: self::parseSuffix($parts[3]),
            isImaginary: self::isImaginary($parts[3]),
        );
    }

    /**
     * Evaluates a hexadecimal floating constant: the mantissa scaled by the
     * binary exponent.
     */
    private static function evaluateHexadecimal(string $mantissa, string $exponent): float
    {
        $point = \strpos($mantissa, '.');
        $power = \max(-self::MAX_EXPONENT, \min(self::MAX_EXPONENT, (int) $exponent));

        // Every digit after the point divides the value by 16
        if ($point !== false) {
            $power -= self::BITS_PER_HEX_DIGIT * (\strlen($mantissa) - $point - 1);
        }

        $digits = \ltrim(\str_replace('.', '', $mantissa), '0');

        if ($digits === '') {
            return 0.0;
        }

        $significand = \intval(\substr($digits, 0, self::MAX_MANTISSA_DIGITS), 16);

        if (\strlen($digits) > self::MAX_MANTISSA_DIGITS) {
            $dropped = \substr($digits, self::MAX_MANTISSA_DIGITS);
            $power += self::BITS_PER_HEX_DIGIT * \strlen($dropped);

            // The value is correctly rounded only if the dropped digits are
            // not lost entirely: whether they are zero decides the rounding
            // of a value that lies exactly halfway
            if (\ltrim($dropped, '0') !== '') {
                $significand |= 1;
            }
        }

        return self::toFloat($significand, $power);
    }

    /**
     * Rounds `mantissa * 2^exponent` to the nearest double, ties to even.
     *
     * A normal value is rounded once, by the conversion of the mantissa. The
     * mantissa of a subnormal one is rounded to its significant bits before
     * the conversion, which then keeps it exact.
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
     * is scaled by `2^-1078`), so a small power is taken in two steps. A large
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
        return match (\strtolower(\str_ireplace(['i', 'j'], '', $suffix))) {
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

    private static function isImaginary(string $suffix): bool
    {
        return \strpbrk($suffix, 'iIjJ') !== false;
    }
}
