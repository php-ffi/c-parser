<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class GnuKeywordSpellingsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string, non-empty-string}>
     */
    public static function spellingsDataProvider(): iterable
    {
        yield '__const' => ['__const int a;', 'const int a;', '__const'];
        yield '__const__' => ['__const__ int a;', 'const int a;', '__const__'];
        yield '__volatile__' => ['__volatile__ int a;', 'volatile int a;', '__volatile__'];
        yield '__restrict' => ['int *__restrict p;', 'int *restrict p;', '__restrict'];
        yield '__restrict__' => ['int *__restrict__ p;', 'int *restrict p;', '__restrict__'];
        yield '__inline' => ['__inline int f(void);', 'inline int f(void);', '__inline'];
        yield '__inline__' => ['__inline__ int f(void);', 'inline int f(void);', '__inline__'];
        yield '__signed__' => ['__signed__ char a;', 'signed char a;', '__signed__'];
        yield '__complex__' => ['__complex__ double a;', '_Complex double a;', '__complex__'];
        yield '__thread' => ['__thread int a;', 'thread_local int a;', '__thread'];
        yield '__typeof__ of a type' => ['__typeof__(int) a;', 'typeof(int) a;', '__typeof__'];
        yield '__typeof of an expression' => ['__typeof(1) a;', 'typeof(1) a;', '__typeof'];
        yield '__typeof_unqual__ of a type' => [
            '__typeof_unqual__(const int) a;',
            'typeof_unqual(const int) a;',
            '__typeof_unqual__',
        ];
        yield '__typeof_unqual of an expression' => ['__typeof_unqual(1) a;', 'typeof_unqual(1) a;', '__typeof_unqual'];
        yield '__alignof__' => ['int a = __alignof__(long);', 'int a = alignof(long);', '__alignof__'];
    }

    #[DataProvider('spellingsDataProvider')]
    public function testSpellingGivesTheTreeOfTheKeyword(string $code, string $keyword, string $spelling): void
    {
        self::assertSame(
            \array_map(NodePrinter::print(...), self::parse($keyword)),
            \array_map(NodePrinter::print(...), self::parse($code)),
        );
    }

    #[DataProvider('spellingsDataProvider')]
    public function testSpellingIsRejectedWithoutTheFeature(string $code, string $keyword, string $spelling): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame($spelling, \substr($code, $e->offset, $e->length ?? 0));

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
