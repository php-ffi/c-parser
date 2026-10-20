<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\StringLiteral;

use FFI\Parser\Node\Literal\EncodingPrefix;
use FFI\Parser\Node\Literal\StringLiteral\StringParser\StringParserResult;

/**
 * Parses the source text of a character constant or a string literal.
 *
 * Escape sequences are expanded as follows:
 * - Characters written as is are copied byte by byte.
 * - A universal character name (`\u00E9`) becomes its UTF-8 encoding.
 * - An octal or hexadecimal escape (`\101`, `\x41`) becomes a single byte
 *   in an unprefixed or `u8` literal and the UTF-8 encoding of the code
 *   point in a wide one (`L`, `u`, `U`).
 * - A value that does not fit (a code unit above the limit of the
 *   character type, a code point above `0x10FFFF` or a surrogate) becomes
 *   U+FFFD. So do both halves of a surrogate pair written as two escapes
 *   in a `u` literal.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.5 Character constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.5 String literals
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class StringParser
{
    /**
     * Matches a simple, octal, hexadecimal escape sequence or a universal character name.
     *
     * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.5 Character constants
     */
    private const ESCAPE_PATTERN = '/\\\\(?:([\'"?\\\\abefnrtvE])|([0-7]{1,3})|x([0-9A-Fa-f]++)'
        . '|u([0-9A-Fa-f]{4})|U([0-9A-Fa-f]{8}))/';

    /**
     * Simple escape sequences by the character after the backslash.
     *
     * @var array<non-empty-string, non-empty-string>
     */
    private const SIMPLE_ESCAPES = [
        "'" => "'",
        '"' => '"',
        '?' => '?',
        '\\' => '\\',
        'a' => "\x07",
        'b' => "\x08",
        'e' => "\e",
        'E' => "\e",
        'f' => "\f",
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        'v' => "\v",
    ];

    private const MAX_CODE_POINT = 0x10_FFFF;

    /**
     * The surrogates have no UTF-8 encoding.
     */
    private const MIN_SURROGATE = 0xD800;

    private const MAX_SURROGATE = 0xDFFF;

    /**
     * The limit of an escape sequence in an unprefixed literal.
     */
    private const MAX_BYTE = 0xFF;

    /**
     * Replacement character, U+FFFD, in UTF-8.
     */
    private const REPLACEMENT = "\xEF\xBF\xBD";

    /**
     * @param non-empty-string $raw text of a `T_CHAR_LITERAL` or `T_STRING_LITERAL` token
     */
    public static function parse(string $raw): StringParserResult
    {
        // The prefix ends at the opening quote, the closing one is the last character
        $quote = \strcspn($raw, '\'"');
        $prefix = EncodingPrefix::tryFrom(\substr($raw, 0, $quote));

        return new StringParserResult(
            value: self::decodeBody(\substr($raw, $quote + 1, -1), $prefix),
            prefix: $prefix,
        );
    }

    private static function decodeBody(string $body, ?EncodingPrefix $prefix): string
    {
        if (!\str_contains($body, '\\')) {
            return $body;
        }

        $result = \preg_replace_callback(
            self::ESCAPE_PATTERN,
            static fn(array $matches): string => self::decodeEscapeSequence($matches, $prefix),
            $body,
        );

        \assert($result !== null);

        return $result;
    }

    /**
     * @param array<array-key, string> $matches
     */
    private static function decodeEscapeSequence(array $matches, ?EncodingPrefix $prefix): string
    {
        if (($matches[1] ?? '') !== '') {
            return self::SIMPLE_ESCAPES[$matches[1]];
        }

        if (($matches[2] ?? '') !== '') {
            return self::encodeCodeUnit((int) \octdec($matches[2]), $prefix);
        }

        if (($matches[3] ?? '') !== '') {
            return self::encodeCodeUnit(self::parseHexadecimal($matches[3]), $prefix);
        }

        $codePoint = ($matches[4] ?? '') !== '' ? $matches[4] : ($matches[5] ?? '');

        return self::encodeCodePoint(self::parseHexadecimal($codePoint));
    }

    /**
     * @return int|null {@see null} if the value does not fit in a native integer
     */
    private static function parseHexadecimal(string $digits): ?int
    {
        $value = \hexdec($digits);

        return \is_int($value) ? $value : null;
    }

    /**
     * Encodes the value of an octal or hexadecimal escape sequence: a byte in an
     * unprefixed or `u8` literal, a code point in a wide one.
     */
    private static function encodeCodeUnit(?int $value, ?EncodingPrefix $prefix): string
    {
        $max = $prefix?->getMaxCodeUnit() ?? self::MAX_BYTE;

        if ($value === null || $value > $max) {
            return self::REPLACEMENT;
        }

        if ($max === self::MAX_BYTE) {
            return \chr($value);
        }

        return self::encodeCodePoint($value);
    }

    private static function encodeCodePoint(?int $value): string
    {
        if ($value === null
            || $value > self::MAX_CODE_POINT
            || ($value >= self::MIN_SURROGATE && $value <= self::MAX_SURROGATE)
        ) {
            return self::REPLACEMENT;
        }

        if (\function_exists('mb_chr')) {
            return \mb_chr($value, 'UTF-8');
        }

        return match (true) {
            $value < 0x80 => \chr($value),
            $value < 0x800 => \chr(0xC0 | $value >> 6)
                . \chr(0x80 | $value & 0x3F),
            $value < 0x1_0000 => \chr(0xE0 | $value >> 12)
                . \chr(0x80 | $value >> 6 & 0x3F)
                . \chr(0x80 | $value & 0x3F),
            default => \chr(0xF0 | $value >> 18)
                . \chr(0x80 | $value >> 12 & 0x3F)
                . \chr(0x80 | $value >> 6 & 0x3F)
                . \chr(0x80 | $value & 0x3F),
        };
    }
}
