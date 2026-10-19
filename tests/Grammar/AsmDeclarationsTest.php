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
final class AsmDeclarationsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'statement' => ['__asm__("nop");', '(asm "nop")'];
        yield 'short spelling' => ['__asm(".symver a, b");', '(asm ".symver a, b")'];
        yield 'concatenated string literals' => ['__asm__("a" "b");', '(asm (concat "a" "b"))'];
        yield 'after the extension keyword' => ['__extension__ __asm__(".globl x");', '(asm ".globl x")'];
        yield 'among the declarations' => [
            'int x; __asm__("nop"); int y;',
            '(variable int x) (asm "nop") (variable int y)',
        ];
    }

    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testStatementIsRejectedAtItsKeywordWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('__asm__("nop");');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "AsmDeclarations" is not enabled', 0, 7],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
