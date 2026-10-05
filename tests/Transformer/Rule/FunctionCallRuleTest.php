<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule;

use FFI\Parser\Transformer\Rule\FunctionCallRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FunctionCallRule::class)]
final class FunctionCallRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new FunctionCallRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'argument' => ['int r = (f)(x);', '(variable int r = (call (paren f) x))'];
        yield 'arguments' => ['int r = (f)(x, y);', '(variable int r = (call (paren f) x y))'];
        yield 'parenthesized comma operator' => [
            'int r = (f)((x, y));',
            '(variable int r = (call (paren f) (paren (, x y))))',
        ];
        yield 'subscript of result' => ['int r = (f)(x)[0];', '(variable int r = ([] (call (paren f) x) 0))'];
        yield 'member of result' => ['int r = (f)(x).m;', '(variable int r = (. (call (paren f) x) m))'];
        yield 'call of result' => ['int r = (f)(x)(y);', '(variable int r = (call (call (paren f) x) y))'];
        yield 'call of result with unknown argument' => [
            'int r = (f)(W)(y);',
            '(variable int r = (call (call (paren f) W) y))',
        ];
        yield 'call of parenthesized function' => [
            'int r = (f)(g)(y);',
            '(variable int r = (call (call (paren f) g) y))',
        ];
    }

    #[DataProvider('rewrittenDeclarationsDataProvider')]
    public function testCastIsTurnedIntoCall(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptDeclarationsDataProvider(): iterable
    {
        yield 'typedef name' => ['int r = (T)(x);'];
        yield 'unknown name' => ['int r = (W)(x);'];
        yield 'operand without parentheses' => ['int r = (f)x;'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testCastIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }

    public function testCallStartsWithTheParenthesizedFunction(): void
    {
        self::assertSame(
            '(variable int@37 r@41 = (call (paren f@46)@45 x@49 y@52)@45)@37',
            self::transform('int r = (f)(x, y);', withOffsets: true),
        );
    }
}
