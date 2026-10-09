<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\AlignOfExpressionOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AlignOfExpressionOperandRule::class)]
final class AlignOfExpressionOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new AlignOfExpressionOperandRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'object' => ['int r = alignof(x);', '(variable int r = (alignof (paren x)))'];
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
        yield 'typedef name' => ['int r = alignof(T);'];
        yield 'unknown name' => ['int r = alignof(W);'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testTypeNameIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }
}
