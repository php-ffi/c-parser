<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\StringLiteral;

use FFI\Parser\Node\Literal\StringLiteral\StringParser\StringParserResult;

/**
 * Parses the source text of a character constant or a string literal: the
 * encoding prefix and the characters between the quotes.
 *
 * Escape sequences are expanded as follows:
 * - Characters written as is are copied byte by byte.
 * - A universal character name (`\u00E9`) becomes its UTF-8 encoding.
 * - An octal or hexadecimal escape (`\101`, `\x41`) becomes a single byte
 *   in an unprefixed or `u8` literal and the UTF-8 encoding of the code
 *   point in a wide one (`L`, `u`, `U`).
 * - A value that does not fit (a byte above `0xFF`, a code point above
 *   `0x10FFFF`) becomes U+FFFD, the exact text is kept by the raw text
 *   of the literal.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.5 Character constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.5 String literals
 */
final class StringParser
{
    /**
     * Matches an escape sequence: a simple escape, an octal one, a
     * hexadecimal one and a universal character name of either size.
     *
     * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.5 Character constants
     */
    private const ESCAPE_PATTERN = '/\\\\(?:([\'"?\\\\abfnrtv])|([0-7]{1,3})|x([0-9A-Fa-f]++)|u([0-9A-Fa-f]{4})|U([0-9A-Fa-f]{8}))/';

    /**
     * Characters of the simple escape sequences, indexed by the character
     * written after the backslash.
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
        'f' => "\f",
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        'v' => "\v",
    ];

    /**
     * The largest code point of Unicode.
     */
    private const MAX_CODE_POINT = 0x10_FFFF;

    /**
     * The largest byte.
     */
    private const MAX_BYTE = 0xFF;

    /**
     * Replacement character, U+FFFD, in UTF-8.
     */
    private const REPLACEMENT = "\xEF\xBF\xBD";

    /**
     * @param non-empty-string $raw text of a `T_CHAR_LITERAL` or a
     *        `T_STRING_LITERAL` token
     */
    public static function parse(string $raw): StringParserResult
    {
        // The prefix ends at the opening quote of either kind, the closing
        // one is the last character.
        $quote = \strcspn($raw, '\'"');
        $prefix = StringTypePrefix::tryFrom(\substr($raw, 0, $quote));

        return new StringParserResult(
            value: self::decodeBody(\substr($raw, $quote + 1, -1), $prefix),
            prefix: $prefix,
        );
    }

    /**
     * Expands the escape sequences of the characters between the quotes.
     */
    private static function decodeBody(string $body, ?StringTypePrefix $prefix): string
    {
        if (!\str_contains($body, '\\')) {
            return $body;
        }

        $isWide = $prefix !== null && $prefix !== StringTypePrefix::Utf8;

        $result = \preg_replace_callback(
            self::ESCAPE_PATTERN,
            static fn(array $matches): string => self::decodeEscapeSequence($matches, $isWide),
            $body,
        );

        \assert($result !== null);

        return $result;
    }

    /**
     * @param array<array-key, string> $matches
     */
    private static function decodeEscapeSequence(array $matches, bool $isWide): string
    {
        if (($matches[1] ?? '') !== '') {
            return self::SIMPLE_ESCAPES[$matches[1]];
        }

        if (($matches[2] ?? '') !== '') {
            return self::encodeCodeUnit((int) \octdec($matches[2]), $isWide);
        }

        if (($matches[3] ?? '') !== '') {
            return self::encodeCodeUnit(self::parseHexadecimal($matches[3]), $isWide);
        }

        $codePoint = ($matches[4] ?? '') !== '' ? $matches[4] : ($matches[5] ?? '');

        return self::encodeCodePoint(self::parseHexadecimal($codePoint));
    }

    /**
     * @return int<-1, max> the value or -1 in case of it does not fit in a
     *         code point at all
     */
    private static function parseHexadecimal(string $digits): int
    {
        $digits = \ltrim($digits, '0');

        // Eight digits always fit in a native integer, a longer escape is
        // out of range of any character type anyway.
        if (\strlen($digits) > 8) {
            return -1;
        }

        return \max(0, (int) \hexdec($digits === '' ? '0' : $digits));
    }

    private static function encodeCodeUnit(int $value, bool $isWide): string
    {
        if ($isWide) {
            return self::encodeCodePoint($value);
        }

        if ($value < 0 || $value > self::MAX_BYTE) {
            return self::REPLACEMENT;
        }

        return \chr($value);
    }

    private static function encodeCodePoint(int $value): string
    {
        if ($value < 0 || $value > self::MAX_CODE_POINT) {
            return self::REPLACEMENT;
        }

        // The extension is not a dependency, and it refuses surrogates
        // (U+D800 to U+DFFF), which are then encoded by hand as well.
        // PHPStan stubs mb_chr() as never returning false, which it does.
        // @phpstan-ignore-next-line
        if (\function_exists('mb_chr') && ($result = \mb_chr($value, 'UTF-8')) !== false) {
            return $result;
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
