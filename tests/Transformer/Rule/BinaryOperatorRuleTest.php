<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule;

use FFI\Parser\Transformer\Rule\BinaryOperatorRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(BinaryOperatorRule::class)]
final class BinaryOperatorRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new BinaryOperatorRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'minus' => ['int r = (x)-y;', '(variable int r = (- (paren x) y))'];
        yield 'plus' => ['int r = (x)+y;', '(variable int r = (+ (paren x) y))'];
        yield 'asterisk' => ['int r = (x)*y;', '(variable int r = (* (paren x) y))'];
        yield 'ampersand' => ['int r = (x)&y;', '(variable int r = (& (paren x) y))'];
        yield 'unary operator after operator' => ['int r = (x)- -y;', '(variable int r = (- (paren x) (-u y)))'];
        yield 'operators in a row' => ['int r = (x)-(y)-z;', '(variable int r = (- (paren x) (- (paren y) z)))'];
        yield 'postfix increment before plus' => ['int r = (x)++ + y;', '(variable int r = (+ (++p (paren x)) y))'];
        yield 'postfix decrement before minus' => ['int r = (x)-- - y;', '(variable int r = (- (--p (paren x)) y))'];
    }

    #[DataProvider('rewrittenDeclarationsDataProvider')]
    public function testCastIsTurnedIntoBinaryOperator(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptDeclarationsDataProvider(): iterable
    {
        yield 'typedef name' => ['int r = (T)-x;'];
        yield 'unknown name' => ['int r = (W)-x;'];
        yield 'other unary operator' => ['int r = (x)~y;'];
        yield 'prefix increment of a typedef name' => ['int r = (T)++x;'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testCastIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }

    public function testBinaryOperatorStartsWithTheParenthesizedName(): void
    {
        self::assertSame(
            '(variable int@37 r@41 = (- (paren x@46)@45 y@49)@45)@37',
            self::transform('int r = (x)-y;', withOffsets: true),
        );
    }
}
