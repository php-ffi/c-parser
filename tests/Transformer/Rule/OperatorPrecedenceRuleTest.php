<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule;

use FFI\Parser\Transformer\Rule\BinaryOperatorRule;
use FFI\Parser\Transformer\Rule\FunctionCallRule;
use FFI\Parser\Transformer\Rule\OperatorPrecedenceRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(OperatorPrecedenceRule::class)]
final class OperatorPrecedenceRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new OperatorPrecedenceRule();
    }

    protected static function createRules(): array
    {
        return [new BinaryOperatorRule(), new FunctionCallRule(), static::createRule()];
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'operator of higher precedence after' => ['int r = (x)-y*z;', '(variable int r = (- (paren x) (* y z)))'];
        yield 'operator of lower precedence after' => ['int r = (x)*y-z;', '(variable int r = (- (* (paren x) y) z))'];
        yield 'operator of higher precedence before' => ['int r = z*(x)-y;', '(variable int r = (- (* z (paren x)) y))'];
        yield 'operators of the same precedence' => [
            'int r = (x)-(y)-z;',
            '(variable int r = (- (- (paren x) (paren y)) z))',
        ];
        yield 'after unary operator' => ['int r = -(x)-y;', '(variable int r = (- (-u (paren x)) y))'];
        yield 'after cast' => ['int r = (T)(x)-y;', '(variable int r = (- (cast T (paren x)) y))'];
        yield 'after sizeof' => ['int r = sizeof -(x)-y;', '(variable int r = (- (sizeof (-u (paren x))) y))'];
        yield 'after call' => ['int r = (f)(x)-y;', '(variable int r = (- (call (paren f) x) y))'];
        yield 'operand of conditional' => ['int r = (x)-y ? z : x;', '(variable int r = (? (- (paren x) y) z x))'];
        yield 'operand of comma' => ['int r = ((x)-y, z);', '(variable int r = (paren (, (- (paren x) y) z)))'];
    }

    #[DataProvider('rewrittenDeclarationsDataProvider')]
    public function testOperatorsAreRearranged(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptDeclarationsDataProvider(): iterable
    {
        yield 'binary operators' => ['int r = x-y*z;'];
        yield 'cast to typedef name' => ['int r = (T)-x*y;'];
        yield 'parenthesized operator' => ['int r = -(x-y);'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testOperatorsAreKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }

    public function testOperatorStartsWithItsLeftOperand(): void
    {
        self::assertSame(
            '(variable int@37 r@41 = (- (* z@45 (paren x@50)@49)@45 y@55)@45)@37',
            self::transform('int r = z * (x) - y;', withOffsets: true),
        );
    }
}
