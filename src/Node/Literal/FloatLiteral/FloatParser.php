<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

use FFI\Parser\Node\Literal\FloatLiteral\FloatParser\FloatParserResult;

/**
 * Parses the source text of a floating constant.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H.5.2 Constants
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
     * Hexadecimal digits of the mantissa that are taken as an integer, 60 bits.
     */
    private const MAX_MANTISSA_DIGITS = 15;

    /**
     * Bound of the written exponent, beyond it the value is infinite or zero.
     */
    private const MAX_EXPONENT = 1 << 40;

    /**
     * Binary exponent of the smallest subnormal double, `0x1p-1074`.
     */
    private const MIN_SUBNORMAL_EXPONENT = -1074;

    /**
     * Binary exponent of the smallest normal double, `0x1p-1022`.
     */
    private const MIN_NORMAL_EXPONENT = -1022;

    /**
     * Exponent of the step a small power of two is scaled in.
     */
    private const SCALE_STEP = 1000;

    private const BITS_PER_HEX_DIGIT = 4;

    /**
     * @param non-empty-string $raw
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
            suffixWidth: self::parseSuffixWidth($parts[3]),
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
            suffixWidth: self::parseSuffixWidth($parts[3]),
            isImaginary: self::isImaginary($parts[3]),
        );
    }

    /**
     * Evaluates the mantissa scaled by the binary exponent.
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

            // Non-zero dropped digits are folded into the lowest bit for the rounding
            if (\ltrim($dropped, '0') !== '') {
                $significand |= 1;
            }
        }

        return self::toFloat($significand, $power);
    }

    /**
     * Rounds `mantissa * 2^exponent` to the nearest double, ties to even.
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
     * Shifts the integer right, rounding the dropped bits half to even.
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
     * Multiplies the value by the power of two, {@see \INF} on overflow.
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
        $type = self::readTypeSuffix($suffix);

        return match ($type) {
            '' => null,
            'f' => FloatTypeSuffix::Float,
            'l' => FloatTypeSuffix::LongDouble,
            'df', 'd32' => FloatTypeSuffix::Decimal32,
            'dd', 'd64' => FloatTypeSuffix::Decimal64,
            'dl', 'd128' => FloatTypeSuffix::Decimal128,
            'q' => FloatTypeSuffix::GnuFloat128,
            'w' => FloatTypeSuffix::GnuFloat80,
            default => self::parseWidthSuffix($type),
        };
    }

    /**
     * Parses `fN`, `fNx`, `dN` and `dNx` of any width N.
     */
    private static function parseWidthSuffix(string $type): ?FloatTypeSuffix
    {
        $isExtended = \str_ends_with($type, 'x');

        return match ($type[0]) {
            'f' => $isExtended ? FloatTypeSuffix::FloatNx : FloatTypeSuffix::FloatN,
            'd' => $isExtended ? FloatTypeSuffix::DecimalNx : FloatTypeSuffix::DecimalN,
            default => null,
        };
    }

    /**
     * @return int<1, max>|null
     */
    private static function parseSuffixWidth(string $suffix): ?int
    {
        if (self::parseSuffix($suffix)?->hasWidth() !== true) {
            return null;
        }

        /** @var int<1, max> */
        return (int) \substr(self::readTypeSuffix($suffix), 1);
    }

    /**
     * Returns the type suffix in lower case without the imaginary one.
     */
    private static function readTypeSuffix(string $suffix): string
    {
        return \strtolower(\str_ireplace(['i', 'j'], '', $suffix));
    }

    private static function isImaginary(string $suffix): bool
    {
        return \strpbrk($suffix, 'iIjJ') !== false;
    }
}
