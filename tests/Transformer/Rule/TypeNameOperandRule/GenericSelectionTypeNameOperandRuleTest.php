<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Expression\GenericSelectionExpression;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\GenericSelectionTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GenericSelectionTypeNameOperandRule::class)]
final class GenericSelectionTypeNameOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new GenericSelectionTypeNameOperandRule();
    }

    public function testTypedefNameIsTurnedIntoTypeName(): void
    {
        self::assertInstanceOf(NamedType::class, self::findOperand('int r = _Generic(T, int: 1);'));
    }

    public function testExpressionIsTurnedIntoTypeName(): void
    {
        self::assertSame(
            '(variable int r = (_Generic (array T 2) int:1))',
            self::transform('int r = _Generic(T[2], int: 1);'),
        );
    }

    public function testNameOfObjectIsKept(): void
    {
        self::assertInstanceOf(IdentifierExpression::class, self::findOperand('int r = _Generic(x, int: 1);'));
    }

    private static function findOperand(string $code): mixed
    {
        $declaration = \array_last(self::transformDeclarations($code));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(GenericSelectionExpression::class, $declaration->initializer);

        return $declaration->initializer->operand;
    }
}
