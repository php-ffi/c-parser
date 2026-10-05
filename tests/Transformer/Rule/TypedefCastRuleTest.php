<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule;

use FFI\Parser\Transformer\Rule\TypedefCastRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TypedefCastRule::class)]
final class TypedefCastRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new TypedefCastRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'minus' => ['int r = (T)-x*y;', '(variable int r = (* (cast T (-u x)) y))'];
        yield 'plus' => ['int r = (T)+x;', '(variable int r = (cast T (+u x)))'];
        yield 'dereference' => ['int r = (T)*x;', '(variable int r = (cast T (*u x)))'];
        yield 'address' => ['int r = (T)&x;', '(variable int r = (cast T (&u x)))'];
        yield 'after binary operator' => ['int r = x+(T)-y;', '(variable int r = (+ x (cast T (-u y))))'];
        yield 'after unary operator' => ['int r = -(T)-y;', '(variable int r = (-u (cast T (-u y))))'];
        yield 'after cast' => ['int r = (U)(T)-y;', '(variable int r = (cast U (cast T (-u y))))'];
        yield 'casts in a row' => ['int r = (T)-(U)-x;', '(variable int r = (cast T (-u (cast U (-u x)))))'];
        yield 'operators of the same precedence' => [
            'int r = x*(T)-y*z;',
            '(variable int r = (* (* x (cast T (-u y))) z))',
        ];
        yield 'operators of lower precedence' => ['int r = (T)-x == y;', '(variable int r = (== (cast T (-u x)) y))'];
        yield 'operand of conditional' => ['int r = (T)-x ? y : z;', '(variable int r = (? (cast T (-u x)) y z))'];
    }

    #[DataProvider('rewrittenDeclarationsDataProvider')]
    public function testCastIsRebuilt(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptDeclarationsDataProvider(): iterable
    {
        yield 'name of object' => ['int r = (x)-y;'];
        yield 'unknown name' => ['int r = (W)-y;'];
        yield 'other operator' => ['int r = (T)/x;'];
        yield 'parenthesized expression' => ['int r = (T+x)-y;'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testExpressionIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }

    public function testUnaryExpressionIsGivenTheOffsetOfItsOperand(): void
    {
        self::assertSame(
            '(variable int@37 r@41 = (* (cast T@46 (-u x@51)@51)@45 y@55)@45)@37',
            self::transform('int r = (T) - x * y;', withOffsets: true),
        );
    }
}
