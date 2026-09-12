<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\EncodingPrefix;
use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Node\Literal\StringLiteral\StringParser;
use FFI\Parser\Parser;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(StringLiteral::class)]
#[CoversClass(StringParser::class)]
final class StringLiteralTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, string}>
     */
    public static function valuesDataProvider(): iterable
    {
        yield 'empty' => ['""', ''];
        yield 'text' => ['"text"', 'text'];
        yield 'UTF-8 as is' => ["\"caf\xC3\xA9\"", "caf\xC3\xA9"];
        yield 'invalid UTF-8 as is' => ["\"\xFF\"", "\xFF"];

        yield 'quotes' => ['"\\\'\\"\\?"', '\'"?'];
        yield 'backslash' => ['"\\\\"', '\\'];
        yield 'escaped backslash before a letter' => ['"\\\\n"', '\\n'];
        yield 'control characters' => ['"\\a\\b\\f\\n\\r\\t\\v"', "\x07\x08\f\n\r\t\v"];
        yield 'escape character of GCC' => ['"\\e\\E"', "\e\e"];

        yield 'octal' => ['"\\101"', 'A'];
        yield 'octal of one digit' => ['"\\0"', "\0"];
        yield 'octal of at most three digits' => ['"\\1011"', 'A1'];
        yield 'octal zeros of every length' => ['"\\0\\00\\000\\0000"', "\0\0\0\0" . '0'];
        yield 'octal of a byte' => ['"\\377"', "\xFF"];
        yield 'octal above a byte' => ['"\\777"', "\u{FFFD}"];

        yield 'hexadecimal' => ['"\\x41"', 'A'];
        yield 'hexadecimal of one digit' => ['"\\x9"', "\t"];
        yield 'hexadecimal with leading zeros' => ['"\\x000041"', 'A'];
        yield 'hexadecimal of a byte' => ['"\\xFF"', "\xFF"];
        yield 'hexadecimal above a byte' => ['"\\x100"', "\u{FFFD}"];
        yield 'hexadecimal beyond any integer' => ['"\\x123456789ABCDEF0123"', "\u{FFFD}"];

        yield 'universal character name' => ['"\\u00E9"', "\u{E9}"];
        yield 'long universal character name' => ['"\\U0001F600"', "\u{1F600}"];
        yield 'universal character name of ASCII' => ['"\\u0041"', 'A'];
        yield 'universal character name above Unicode' => ['"\\U00110000"', "\u{FFFD}"];
        yield 'universal character name of a surrogate' => ['"\\uD800"', "\u{FFFD}"];
        yield 'hexadecimal ended by a non-hexadecimal character' => ['"\\x41g"', 'Ag'];

        yield 'everything at once' => ['"a\\n\\x41\\101\\u00E9\\U0001F600"', "a\nAA\u{E9}\u{1F600}"];
    }

    #[DataProvider('valuesDataProvider')]
    public function testValueIsDecoded(string $raw, string $value): void
    {
        $literal = StringLiteral::parse($raw, 0);

        self::assertSame($raw, $literal->raw);
        self::assertSame($value, $literal->value);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, EncodingPrefix|null, string}>
     */
    public static function prefixesDataProvider(): iterable
    {
        yield 'none' => ['"\\xFF"', null, "\xFF"];
        yield 'u8' => ['u8"\\xFF"', EncodingPrefix::Utf8, "\xFF"];
        yield 'u' => ['u"\\xFF"', EncodingPrefix::Utf16, "\u{FF}"];
        yield 'U' => ['U"\\x1F600"', EncodingPrefix::Utf32, "\u{1F600}"];
        yield 'L' => ['L"\\x1234"', EncodingPrefix::Wide, "\u{1234}"];
        yield 'L octal' => ['L"\\777"', EncodingPrefix::Wide, "\u{1FF}"];
        yield 'L octal of at most three digits' => ['L"\\1234"', EncodingPrefix::Wide, 'S4'];
        yield 'u above a code unit' => ['u"\\x10000"', EncodingPrefix::Utf16, "\u{FFFD}"];
        yield 'u surrogate pair' => ['u"\\xD83D\\xDE00"', EncodingPrefix::Utf16, "\u{FFFD}\u{FFFD}"];
        yield 'U above Unicode' => ['U"\\xFFFFFFFF"', EncodingPrefix::Utf32, "\u{FFFD}"];
        yield 'u8 above a byte' => ['u8"\\x100"', EncodingPrefix::Utf8, "\u{FFFD}"];
        yield 'u8 octal of a byte' => ['u8"\\377"', EncodingPrefix::Utf8, "\xFF"];
        yield 'u largest code unit' => ['u"\\xFFFF"', EncodingPrefix::Utf16, "\u{FFFF}"];
        yield 'u surrogate' => ['u"\\xDFFF"', EncodingPrefix::Utf16, "\u{FFFD}"];
        yield 'U largest code point' => ['U"\\U0010FFFF"', EncodingPrefix::Utf32, "\u{10FFFF}"];
        yield 'L above 32 bits' => ['L"\\x100000000"', EncodingPrefix::Wide, "\u{FFFD}"];
    }

    /**
     * An octal or hexadecimal escape is a byte in a narrow literal and a code
     * point in a wide one.
     */
    #[DataProvider('prefixesDataProvider')]
    public function testPrefixSelectsTheEncodingOfEscapes(string $raw, ?EncodingPrefix $prefix, string $value): void
    {
        $literal = StringLiteral::parse($raw, 0);

        self::assertSame($prefix, $literal->prefix);
        self::assertSame($value, $literal->value);
    }

    public function testOffsetIsKept(): void
    {
        self::assertSame(42, StringLiteral::parse('""', 42)->offset);
    }

    /**
     * @return iterable<non-empty-string, array{string, EncodingPrefix|null}>
     */
    public static function builtLiteralsDataProvider(): iterable
    {
        yield 'text' => ['text', null];
        yield 'empty' => ['', null];
        yield 'quotes and backslashes' => ['"\\\'', null];
        yield 'control characters' => ["\0\n\t\x01\x7F", null];
        yield 'bytes above ASCII' => ["caf\xC3\xA9", EncodingPrefix::Utf8];
        yield 'wide' => ['wide', EncodingPrefix::Wide];
    }

    /**
     * A literal built without the source text gets one that the parser
     * accepts and that is parsed back into the same literal.
     */
    #[DataProvider('builtLiteralsDataProvider')]
    public function testBuiltLiteralGetsValidSourceText(string $value, ?EncodingPrefix $prefix): void
    {
        $raw = (new StringLiteral($value, $prefix))->raw;
        $literal = StringLiteral::parse($raw);

        self::assertTrue((new Parser())->validate('static_assert(1, ' . $raw . ');'));
        self::assertSame($value, $literal->value);
        self::assertSame($prefix, $literal->prefix);
    }
}
