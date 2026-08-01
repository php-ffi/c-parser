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
    private const PATTERN = '/^(.+?)([fF](?:128x?|64x?|32x?|16)|[dD](?:128|64x?|32)|df|DF|dd|DD|dl|DL|[fFlL])?$/';

    /**
     * Matches a hexadecimal number: the whole part, the fraction and the
     * binary exponent.
     */
    private const HEXADECIMAL_PATTERN = '/^0[xX]([0-9A-Fa-f]*)\.?([0-9A-Fa-f]*)[pP]([+-]?[0-9]+)$/';

    /**
     * Hexadecimal digits that always fit in a PHP integer (60 bits). The
     * mantissa of a double has 53 bits, so the digits after them do not
     * change the value.
     */
    private const MAX_MANTISSA_DIGITS = 15;

    /**
     * The largest power of two {@see \PHP_FLOAT_MAX} and the smallest
     * normal number are scaled by at once.
     */
    private const MAX_SCALE_STEP = 1000;

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

        $exponent = (int) $exponent - 4 * \strlen($fraction);
        $mantissa = \ltrim($whole . $fraction, '0');

        if ($mantissa === '') {
            return 0.0;
        }

        if (\strlen($mantissa) > self::MAX_MANTISSA_DIGITS) {
            $exponent += 4 * (\strlen($mantissa) - self::MAX_MANTISSA_DIGITS);
            $mantissa = \substr($mantissa, 0, self::MAX_MANTISSA_DIGITS);
        }

        return self::scale((float) \intval($mantissa, 16), $exponent);
    }

    /**
     * Multiplies the value by the power of two step by step, since the power
     * alone may not fit in a float while the product does
     * (`0x0.001p1030`).
     */
    private static function scale(float $value, int $exponent): float
    {
        while ($exponent > self::MAX_SCALE_STEP) {
            $value *= 2 ** self::MAX_SCALE_STEP;
            $exponent -= self::MAX_SCALE_STEP;
        }

        while ($exponent < -self::MAX_SCALE_STEP) {
            $value *= 2 ** -self::MAX_SCALE_STEP;
            $exponent += self::MAX_SCALE_STEP;
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
            default => null,
        };
    }
}
