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
final class BasedPointersTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'among the specifiers' => ['char __based(p) *q;', '(variable __based(p) (ptr char) q)'];
        yield 'typedef name' => [
            'typedef int __based(pBased) * pBasedPtr;',
            '(typedef typedef __based(pBased) (ptr int) pBasedPtr)',
        ];
        yield 'after a pointer' => ['char * __based(p) *q;', '(variable (ptr (ptr char __based(p))) q)'];
        yield 'base of a segment' => [
            'void __based(__segname("_CODE")) *p;',
            '(variable __based((call __segname "_CODE")) (ptr void) p)',
        ];
        yield 'void base' => ['char * __based(void) p;', '(variable (ptr char __based((tokens void))) p)'];
    }

    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testBaseIsRejectedAtItsKeywordWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('char __based(p) *q;');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "BasedPointers" is not enabled', 5, 7],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
