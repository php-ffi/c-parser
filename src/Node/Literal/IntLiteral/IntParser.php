<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

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
     * @param non-empty-string $raw text of a `T_INT_LITERAL` token
     */
    public static function parse(string $raw): IntParserResult
    {
        $literal = \str_replace("'", '', $raw);
        $base = self::parseBase($literal);

        if ($base === IntBase::Hexadecimal || $base === IntBase::Binary) {
            $literal = \substr($literal, 2);
        }

        $suffixOffset = self::getSuffixOffset($literal);
        $digits = self::trimLeadingZeros(\substr($literal, 0, $suffixOffset));
        /** @var int<0, max> */
        $value = \intval($digits, $base->value);

        return new IntParserResult(
            value: $value,
            // The value saturates at PHP_INT_MAX, only then it differs from
            // the exact one and the digits have to be converted
            decimal: $value === \PHP_INT_MAX ? self::toDecimal($digits, $base) : (string) $value,
            base: $base,
            suffix: self::parseSuffix(\substr($literal, $suffixOffset)),
        );
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
     * Returns the offset the suffix starts at, the length of the literal in
     * case of there is no suffix.
     *
     * @param string $literal digits followed by the suffix, without a prefix
     */
    private static function getSuffixOffset(string $literal): int
    {
        $offset = \strlen($literal);

        // A suffix letter is not a digit of any base, except "b", which is a
        // hexadecimal digit unless it follows the "w" of the "wb" suffix.
        while ($offset > 1) {
            $char = $literal[$offset - 1];

            if (\str_contains('uUlL', $char)) {
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
        if ($base === IntBase::Decimal && \is_numeric($digits)) {
            return $digits;
        }

        return BaseConverterFactory::createDefault()->toDecimal($digits, $base->value);
    }

    private static function parseSuffix(string $suffix): ?IntTypeSuffix
    {
        $isUnsigned = \stripos($suffix, 'u') !== false;

        return match (\strtolower(\str_ireplace('u', '', $suffix))) {
            'l' => $isUnsigned ? IntTypeSuffix::UnsignedLong : IntTypeSuffix::Long,
            'll' => $isUnsigned ? IntTypeSuffix::UnsignedLongLong : IntTypeSuffix::LongLong,
            'wb' => $isUnsigned ? IntTypeSuffix::UnsignedBitPrecise : IntTypeSuffix::BitPrecise,
            default => $isUnsigned ? IntTypeSuffix::Unsigned : null,
        };
    }
}
