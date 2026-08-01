<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\CharLiteral;
use FFI\Parser\Node\Literal\StringLiteral\StringTypePrefix;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CharLiteral::class)]
final class CharLiteralTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, string, StringTypePrefix|null}>
     */
    public static function valuesDataProvider(): iterable
    {
        yield 'character' => ["'a'", 'a', null];
        yield 'multicharacter' => ["'ab'", 'ab', null];
        yield 'double quote as is' => ["'\"'", '"', null];
        yield 'escaped quote' => ["'\\''", "'", null];
        yield 'null character' => ["'\\0'", "\0", null];
        yield 'hexadecimal' => ["'\\x41'", 'A', null];
        yield 'universal character name' => ["'\\u00E9'", "\u{E9}", null];
        yield 'u8' => ["u8'a'", 'a', StringTypePrefix::Utf8];
        yield 'u' => ["u'\\xFF'", "\u{FF}", StringTypePrefix::Utf16];
        yield 'U' => ["U'a'", 'a', StringTypePrefix::Utf32];
        yield 'L' => ["L'\\xFFFF'", "\u{FFFF}", StringTypePrefix::Wide];
    }

    #[DataProvider('valuesDataProvider')]
    public function testValueIsDecoded(string $raw, string $value, ?StringTypePrefix $prefix): void
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
     * A constant built without the source text gets one that is parsed back
     * into the same constant.
     */
    public function testBuiltConstantGetsValidSourceText(): void
    {
        $constant = CharLiteral::parse((new CharLiteral("'\\\n", StringTypePrefix::Wide))->raw);

        self::assertSame("'\\\n", $constant->value);
        self::assertSame(StringTypePrefix::Wide, $constant->prefix);
    }
}
