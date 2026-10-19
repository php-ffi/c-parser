<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class AlignOfExpressionsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function operandsDataProvider(): iterable
    {
        yield 'expression without parentheses' => ['int a; int x = __alignof__ a;', '(variable int x = (alignof a))'];
        yield 'member access' => [
            'struct S { int a; } s; int x = __alignof__ s.a + 1;',
            '(variable int x = (+ (alignof (. s a)) 1))',
        ];
        yield 'parenthesized object' => ['int a; int x = __alignof__(a);', '(variable int x = (alignof (paren a)))'];
        yield 'subscript' => [
            'int a[3]; int x = __alignof__(a[0]);',
            '(variable int x = (alignof (paren ([] a 0))))',
        ];
        yield 'compound literal' => ['int x = alignof (int){1};', '(variable int x = (alignof (compound int {1})))'];
        yield 'operand of a prefix operator' => [
            'int a; int x = - __alignof__ (a) * 2;',
            '(variable int x = (* (-u (alignof (paren a))) 2))',
        ];
        yield 'typedef name' => ['typedef int T; int x = __alignof__(T);', '(variable int x = (alignof T))'];
        yield 'array of a typedef name' => [
            'typedef int T; int x = __alignof__(T[3]);',
            '(variable int x = (alignof (array T 3)))',
        ];
        yield 'array of an undeclared name' => ['int x = alignof(T[3]);', '(variable int x = (alignof (array T 3)))'];
    }

    #[DataProvider('operandsDataProvider')]
    public function testOperandIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(\array_last(self::parse($code))));
    }
}
