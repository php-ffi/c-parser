<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral\IntParser\IntBase;
use FFI\Parser\Node\Literal\IntLiteral\IntParser\IntParserResult;

/**
 * Parses the source text of an integer constant.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class IntParser
{
    /**
     * Matches the MSVC suffix of a sized integer type, `i64` or `ui64`, at the end of the literal.
     */
    private const MSVC_SUFFIX_PATTERN = '/[uU]?[iI](?:8|16|32|64)$/';

    /**
     * @param non-empty-string $raw
     */
    public static function parse(string $raw): IntParserResult
    {
        $literal = \str_replace("'", '', $raw);
        $base = self::parseBase($literal);

        if ($base === IntBase::Hexadecimal || $base === IntBase::Binary || \stripos($literal, '0o') === 0) {
            $literal = \substr($literal, 2);
        }

        $suffixOffset = self::getSuffixOffset($literal);
        $digits = self::trimLeadingZeros(\substr($literal, 0, $suffixOffset));
        $suffix = \substr($literal, $suffixOffset);
        /** @var int<0, max> */
        $value = \intval($digits, $base->value);

        return new IntParserResult(
            value: $value,
            // The digits are converted only if the value saturated at PHP_INT_MAX
            decimal: $value === \PHP_INT_MAX ? self::toDecimal($digits, $base) : (string) $value,
            suffix: self::parseSuffix($suffix),
            isImaginary: self::isImaginary($suffix),
        );
    }

    /**
     * Whether the suffix makes the constant imaginary, the `i` of `i64` aside.
     */
    private static function isImaginary(string $suffix): bool
    {
        return !\ctype_digit(\substr($suffix, -1)) && \strpbrk($suffix, 'iIjJ') !== false;
    }

    private static function parseBase(string $literal): IntBase
    {
        if (!\str_starts_with($literal, '0')) {
            return IntBase::Decimal;
        }

        return match ($literal[1] ?? '') {
            'x', 'X' => IntBase::Hexadecimal,
            'b', 'B' => IntBase::Binary,
            default => IntBase::Octal,
        };
    }

    /**
     * Returns the offset the suffix starts at, the length of the literal if none.
     *
     * @param string $literal digits followed by the suffix, without a prefix
     */
    private static function getSuffixOffset(string $literal): int
    {
        if (\preg_match(self::MSVC_SUFFIX_PATTERN, $literal, $matches, \PREG_OFFSET_CAPTURE) === 1) {
            return $matches[0][1];
        }

        $offset = \strlen($literal);

        // A suffix letter is not a digit of any base, except "b", which is a
        // hexadecimal digit unless it follows the "w" of the "wb" suffix.
        while ($offset > 1) {
            $char = $literal[$offset - 1];

            if (\str_contains('uUlLiIjJ', $char)) {
                --$offset;
            } elseif (\str_contains('bB', $char) && \str_contains('wW', $literal[$offset - 2])) {
                $offset -= 2;
            } else {
                break;
            }
        }

        return $offset;
    }

    /**
     * @return non-empty-string the digits without the leading zeros or a
     *         single zero
     */
    private static function trimLeadingZeros(string $digits): string
    {
        $digits = \ltrim($digits, '0');

        if ($digits === '') {
            return '0';
        }

        return $digits;
    }

    /**
     * @param non-empty-string $digits without the leading zeros
     * @return numeric-string
     */
    private static function toDecimal(string $digits, IntBase $base): string
    {
        if ($base === IntBase::Decimal) {
            \assert(\is_numeric($digits));

            return $digits;
        }

        return BaseConverter::toDecimal($digits, $base->value);
    }

    private static function parseSuffix(string $suffix): ?IntTypeSuffix
    {
        $isUnsigned = \stripos($suffix, 'u') !== false;
        $suffix = \strtolower(\str_ireplace(['u', 'i', 'j'], '', $suffix));

        if ($isUnsigned) {
            return match ($suffix) {
                'l' => IntTypeSuffix::UnsignedLong,
                'll' => IntTypeSuffix::UnsignedLongLong,
                'wb' => IntTypeSuffix::UnsignedBitPrecise,
                // The MSVC suffixes without the "i"
                '8' => IntTypeSuffix::UnsignedInt8,
                '16' => IntTypeSuffix::UnsignedInt16,
                '32' => IntTypeSuffix::UnsignedInt32,
                '64' => IntTypeSuffix::UnsignedInt64,
                default => IntTypeSuffix::Unsigned,
            };
        }

        return match ($suffix) {
            'l' => IntTypeSuffix::Long,
            'll' => IntTypeSuffix::LongLong,
            'wb' => IntTypeSuffix::BitPrecise,
            // The MSVC suffixes without the "i"
            '8' => IntTypeSuffix::Int8,
            '16' => IntTypeSuffix::Int16,
            '32' => IntTypeSuffix::Int32,
            '64' => IntTypeSuffix::Int64,
            default => null,
        };
    }
}
