<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\SizeOfExpressionOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SizeOfExpressionOperandRule::class)]
#[CoversClass(ExpressionOperandRule::class)]
final class SizeOfExpressionOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new SizeOfExpressionOperandRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'object' => ['int r = sizeof(x);', '(variable int r = (sizeof (paren x)))'];
        yield 'parameter' => [
            'void h(int T, int a[sizeof(T)]);',
            '(function (fn void (int T, (array int (sizeof (paren T))) a)) h)',
        ];
        yield 'enumeration constant' => ['enum { A, B = sizeof(A) };', '(tag enum _ { A, B=(sizeof (paren A)) })'];
    }

    #[DataProvider('rewrittenDeclarationsDataProvider')]
    public function testTypeNameIsTurnedIntoExpression(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptDeclarationsDataProvider(): iterable
    {
        yield 'typedef name' => ['int r = sizeof(T);'];
        yield 'unknown name' => ['int r = sizeof(W);'];
        yield 'type that is not a name' => ['int r = sizeof(int);'];
        yield 'parameter out of its prototype' => ['void h(int T); int r = sizeof(T);'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testTypeNameIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }

    public function testParenthesisIsGivenTheOffsetOfTheName(): void
    {
        self::assertSame(
            '(variable int@37 r@41 = (sizeof (paren x@53)@53)@45)@37',
            self::transform('int r = sizeof (x);', withOffsets: true),
        );
    }
}
