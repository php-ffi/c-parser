<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class EscapeETest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, int<0, max>}>
     */
    public static function escapesDataProvider(): iterable
    {
        yield 'string literal' => ['char *s = "a\\eb";', 10, 6];
        yield 'capital E' => ['char *s = "\\E";', 10, 4];
        yield 'character constant' => ["char c = '\\e';", 9, 4];
        yield 'after an escaped backslash' => ['char *s = "\\\\\\e";', 10, 6];
        yield 'second of the concatenated literals' => ['char *s = "a" "\\e";', 14, 4];
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    #[DataProvider('escapesDataProvider')]
    public function testEscapeIsRejectedAtItsLiteralWithoutTheFeature(string $code, int $offset, int $length): void
    {
        self::assertNotSame([], self::parse($code));

        try {
            (new Parser(preset: Preset::c23()))->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "EscapeE" is not enabled', $offset, $length],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function escapedBackslashesDataProvider(): iterable
    {
        yield 'string literal' => ['char *s = "\\\\e";'];
        yield 'character constant' => ["char c = '\\\\E';"];
        yield 'hexadecimal digit' => ['char *s = "\\x1e";'];
        yield 'universal character name' => ['char *s = "\\u00e9";'];
    }

    /**
     * @param non-empty-string $code
     */
    #[DataProvider('escapedBackslashesDataProvider')]
    public function testLetterEAfterAnotherEscapeIsAcceptedWithoutTheFeature(string $code): void
    {
        self::assertNotSame([], (new Parser(preset: Preset::c23()))->parse($code));
    }
}
