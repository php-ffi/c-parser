<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\CharLiteral;
use FFI\Parser\Node\Literal\EncodingPrefix;
use FFI\Parser\Node\Literal\StringLiteral\StringParser;
use FFI\Parser\Parser;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CharLiteral::class)]
#[CoversClass(StringParser::class)]
final class CharLiteralTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, string, EncodingPrefix|null}>
     */
    public static function valuesDataProvider(): iterable
    {
        yield 'character' => ["'a'", 'a', null];
        yield 'multicharacter' => ["'ab'", 'ab', null];
        yield 'double quote as is' => ["'\"'", '"', null];
        yield 'escaped quote' => ["'\\''", "'", null];
        yield 'null character' => ["'\\0'", "\0", null];
        yield 'hexadecimal' => ["'\\x41'", 'A', null];
        yield 'octal' => ["'\\101'", 'A', null];
        yield 'simple escape' => ["'\\n'", "\n", null];
        yield 'octal above a byte' => ["'\\400'", "\u{FFFD}", null];
        yield 'u above a code unit' => ["u'\\x10000'", "\u{FFFD}", EncodingPrefix::Utf16];
        yield 'universal character name' => ["'\\u00E9'", "\u{E9}", null];
        yield 'u8' => ["u8'a'", 'a', EncodingPrefix::Utf8];
        yield 'u' => ["u'\\xFF'", "\u{FF}", EncodingPrefix::Utf16];
        yield 'U' => ["U'a'", 'a', EncodingPrefix::Utf32];
        yield 'L' => ["L'\\xFFFF'", "\u{FFFF}", EncodingPrefix::Wide];
    }

    #[DataProvider('valuesDataProvider')]
    public function testValueIsDecoded(string $raw, string $value, ?EncodingPrefix $prefix): void
    {
        $literal = CharLiteral::parse($raw, 0);

        self::assertSame($raw, $literal->raw);
        self::assertSame($value, $literal->value);
        self::assertSame($prefix, $literal->prefix);
    }

    public function testOffsetIsKept(): void
    {
        self::assertSame(42, CharLiteral::parse("'a'", 42)->offset);
    }

    /**
     * A constant built without the source text gets one that the parser
     * accepts and that is parsed back into the same constant.
     */
    public function testBuiltConstantGetsValidSourceText(): void
    {
        $raw = (new CharLiteral("'\\\n", EncodingPrefix::Wide))->raw;
        $constant = CharLiteral::parse($raw);

        self::assertTrue((new Parser())->validate('static_assert(' . $raw . ');'));
        self::assertSame("'\\\n", $constant->value);
        self::assertSame(EncodingPrefix::Wide, $constant->prefix);
    }
}
