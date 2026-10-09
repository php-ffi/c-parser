<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsSpecifier;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsTypeSpecifier;
use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\AlignAsExpressionOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AlignAsExpressionOperandRule::class)]
final class AlignAsExpressionOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new AlignAsExpressionOperandRule();
    }

    public function testTypeNameIsTurnedIntoExpression(): void
    {
        $declaration = \array_last(self::transformDeclarations('alignas(x) int r;'));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(AlignAsSpecifier::class, $declaration->specifiers[0]);
        self::assertInstanceOf(IdentifierExpression::class, $declaration->specifiers[0]->operand);
    }

    public function testTypedefNameIsKept(): void
    {
        $declaration = \array_last(self::transformDeclarations('alignas(T) int r;'));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(AlignAsTypeSpecifier::class, $declaration->specifiers[0]);
    }
}
