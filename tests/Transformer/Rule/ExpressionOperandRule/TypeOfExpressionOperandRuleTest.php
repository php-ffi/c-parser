<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Type\TypeOfType;
use FFI\Parser\Node\Type\TypeOfTypeType;
use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\TypeOfExpressionOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TypeOfExpressionOperandRule::class)]
final class TypeOfExpressionOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new TypeOfExpressionOperandRule();
    }

    public function testTypeNameIsTurnedIntoExpression(): void
    {
        $declaration = \array_last(self::transformDeclarations('typeof(x) r;'));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(TypeOfType::class, $declaration->type);
        self::assertInstanceOf(IdentifierExpression::class, $declaration->type->operand);
    }

    public function testTypedefNameIsKept(): void
    {
        $declaration = \array_last(self::transformDeclarations('typeof(T) r;'));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(TypeOfTypeType::class, $declaration->type);
    }
}
