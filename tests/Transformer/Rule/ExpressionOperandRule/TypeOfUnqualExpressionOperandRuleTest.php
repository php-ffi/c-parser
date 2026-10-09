<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Type\TypeOfUnqualType;
use FFI\Parser\Node\Type\TypeOfUnqualTypeType;
use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\TypeOfUnqualExpressionOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TypeOfUnqualExpressionOperandRule::class)]
final class TypeOfUnqualExpressionOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new TypeOfUnqualExpressionOperandRule();
    }

    public function testTypeNameIsTurnedIntoExpression(): void
    {
        $declaration = \array_last(self::transformDeclarations('typeof_unqual(x) r;'));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(TypeOfUnqualType::class, $declaration->type);
        self::assertInstanceOf(IdentifierExpression::class, $declaration->type->operand);
    }

    public function testTypedefNameIsKept(): void
    {
        $declaration = \array_last(self::transformDeclarations('typeof_unqual(T) r;'));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(TypeOfUnqualTypeType::class, $declaration->type);
    }
}
