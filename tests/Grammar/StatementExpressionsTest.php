<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class StatementExpressionsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function expressionsDataProvider(): iterable
    {
        yield 'declaration and expression' => [
            'int x = ({ int y = 1; y * 2; });',
            '(variable int x = ({ int y = 1; y * 2; }))',
        ];
        yield 'operand of a binary operator' => ['int x = ({ 1; }) + 2;', '(variable int x = (+ ({ 1; }) 2))'];
        yield 'operand of a cast to a typedef name' => [
            'typedef int T; int x = (T)({ 1; });',
            '(variable int x = (cast T ({ 1; })))',
        ];
        yield 'operand of sizeof' => ['int x = sizeof ({ 1; });', '(variable int x = (sizeof ({ 1; })))'];
        yield 'nested braces' => [
            'int x = ({ if (a) { b; } c; })[0];',
            '(variable int x = ([] ({ if (a) { b; } c; }) 0))',
        ];
        yield 'brace in a string' => ['int x = ({ "}"; });', '(variable int x = ({ "}"; }))'];
        yield 'empty body' => ['int x = ({});', '(variable int x = ({}))'];
        yield 'parenthesized' => ['int x = (({ 1; }));', '(variable int x = (paren ({ 1; })))'];
    }

    #[DataProvider('expressionsDataProvider')]
    public function testExpressionIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(\array_last(self::parse($code))));
    }

    public function testUnclosedExpressionIsRejected(): void
    {
        self::assertSyntaxError('int x = ({ 1; };', 15, ';');
    }
}
