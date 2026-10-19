<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class PointerModifiersTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function modifiersDataProvider(): iterable
    {
        yield 'after a pointer' => ['int * __ptr64 p;', '(variable (ptr int __ptr64) p)'];
        yield 'several after a pointer' => ['int * __uptr __ptr32 q;', '(variable (ptr int __uptr __ptr32) q)'];
        yield 'after the qualifiers of a pointer' => ['int * const __ptr64 p;', '(variable [const (ptr int __ptr64)] p)'];
        yield 'among the specifiers' => [
            'typedef unsigned long __unaligned *PDWORD;',
            '(typedef typedef __unaligned (ptr (unsigned long _)) PDWORD)',
        ];
        yield 'after the type specifier' => ['typedef int __w64 INT_PTR;', '(typedef typedef __w64 int INT_PTR)'];
        yield 'before the type specifier' => ['__ptr64 int *x;', '(variable __ptr64 (ptr int) x)'];
        yield 'unnamed parameter' => ['void f(int * __ptr64);', '(function (fn void ((ptr int __ptr64))) f)'];
        yield 'pointer to a function' => ['int (* __ptr64 f)(void);', '(variable (ptr (fn int (void)) __ptr64) f)'];
        yield 'member' => ['struct S { void * __ptr64 p; };', '(tag struct S { (ptr void __ptr64) p })'];
    }

    #[DataProvider('modifiersDataProvider')]
    public function testModifierIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(\array_last(self::parse($code))));
    }

    public function testModifierAtTheStartOfParenthesizedDeclaratorIsRejected(): void
    {
        self::assertSyntaxError('int (__ptr64 *p);', 13, '*');
    }
}
