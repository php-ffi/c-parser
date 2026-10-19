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
final class BlockPointersTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'block pointer' => ['void (^b)(int);', '(variable (block (fn void (int))) b)'];
        yield 'qualified block pointer' => ['void (^const b)(void);', '(variable [const (block (fn void (void)))] b)'];
        yield 'array of block pointers' => ['void (^a[10])(int);', '(variable (array (block (fn void (int))) 10) a)'];
        yield 'pointer to a block pointer' => ['int (^(*p))(void);', '(variable (ptr (block (fn int (void)))) p)'];
        yield 'block returning a function pointer' => [
            'void (*(^f)(void))(int);',
            '(variable (block (fn (ptr (fn void (int))) (void))) f)',
        ];
        yield 'typedef name' => [
            'typedef int (^Blk)(int, char);',
            '(typedef typedef (block (fn int (int, char))) Blk)',
        ];
        yield 'unnamed parameter' => ['void f(void (^)(int));', '(function (fn void ((block (fn void (int))))) f)'];
        yield 'type name' => ['int x = sizeof(int (^)(void));', '(variable int x = (sizeof (block (fn int (void)))))'];
        yield 'calling convention of the block' => [
            'void (__stdcall ^b)(void);',
            '(variable (block (fn void (void) __stdcall)) b)',
        ];
        yield 'modified typedef name' => [
            'typedef unsigned __int64 (^U)(void);',
            '(typedef typedef (block (fn (unsigned __int64) (void))) U)',
        ];
        yield 'exclusive or in sizeof' => [
            'int x = sizeof(a ^ (b));',
            '(variable int x = (sizeof (paren (^ a (paren b)))))',
        ];
    }

    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testBlockPointerIsRejectedAtItsCaretWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('void (^b)(int);');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "BlockPointers" is not enabled', 6, 1],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
