<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Node\Literal\StringLiteral\StringDecoder;
use FFI\Parser\Node\Literal\StringLiteral\StringTypePrefix;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(StringLiteral::class)]
#[CoversClass(StringDecoder::class)]
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

        yield 'octal' => ['"\\101"', 'A'];
        yield 'octal of one digit' => ['"\\0"', "\0"];
        yield 'octal of at most three digits' => ['"\\1011"', 'A1'];
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
        yield 'universal character name of a surrogate' => ['"\\uD800"', "\xED\xA0\x80"];

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
     * @return iterable<non-empty-string, array{non-empty-string, StringTypePrefix|null, string}>
     */
    public static function prefixesDataProvider(): iterable
    {
        yield 'none' => ['"\\xFF"', null, "\xFF"];
        yield 'u8' => ['u8"\\xFF"', StringTypePrefix::Utf8, "\xFF"];
        yield 'u' => ['u"\\xFF"', StringTypePrefix::Utf16, "\u{FF}"];
        yield 'U' => ['U"\\x1F600"', StringTypePrefix::Utf32, "\u{1F600}"];
        yield 'L' => ['L"\\x1234"', StringTypePrefix::Wide, "\u{1234}"];
        yield 'L octal' => ['L"\\777"', StringTypePrefix::Wide, "\u{1FF}"];
    }

    /**
     * An octal or hexadecimal escape is a byte in a narrow literal and a code
     * point in a wide one.
     */
    #[DataProvider('prefixesDataProvider')]
    public function testPrefixSelectsTheEncodingOfEscapes(string $raw, ?StringTypePrefix $prefix, string $value): void
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
     * @return iterable<non-empty-string, array{string, StringTypePrefix|null}>
     */
    public static function builtLiteralsDataProvider(): iterable
    {
        yield 'text' => ['text', null];
        yield 'empty' => ['', null];
        yield 'quotes and backslashes' => ['"\\\'', null];
        yield 'control characters' => ["\0\n\t\x01\x7F", null];
        yield 'bytes above ASCII' => ["caf\xC3\xA9", StringTypePrefix::Utf8];
        yield 'wide' => ['wide', StringTypePrefix::Wide];
    }

    /**
     * A literal built without the source text gets one that is parsed back
     * into the same literal.
     */
    #[DataProvider('builtLiteralsDataProvider')]
    public function testBuiltLiteralGetsValidSourceText(string $value, ?StringTypePrefix $prefix): void
    {
        $literal = StringLiteral::parse((new StringLiteral($value, $prefix))->raw);

        self::assertSame($value, $literal->value);
        self::assertSame($prefix, $literal->prefix);
    }
}
