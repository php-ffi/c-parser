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
final class EmptyRecordsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function recordsDataProvider(): iterable
    {
        yield 'structure' => ['struct S {};', '(tag struct S {  })'];
        yield 'union of a variable' => ['union U {} u;', '(variable union U {  } u)'];
        yield 'anonymous structure' => ['struct { } x;', '(variable struct _ {  } x)'];
        yield 'static assertion alone' => ['struct S { static_assert(1); };', '(tag struct S { (static_assert 1) })'];
    }

    #[DataProvider('recordsDataProvider')]
    public function testRecordIsParsed(string $code, string $expected): void
    {
        self::assertSame([$expected], \array_map(NodePrinter::print(...), self::parse($code)));
    }

    public function testEmptyRecordIsRejectedAtItsBraceWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('struct S {};');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "EmptyRecords" is not enabled', 9, 1],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
