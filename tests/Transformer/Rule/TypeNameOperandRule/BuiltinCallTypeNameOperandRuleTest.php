<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Expression\BuiltinCallExpression;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\BuiltinCallTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BuiltinCallTypeNameOperandRule::class)]
final class BuiltinCallTypeNameOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new BuiltinCallTypeNameOperandRule();
    }

    public function testArgumentsAreTurnedIntoTypeNames(): void
    {
        self::assertSame(
            '(variable int r = (__builtin_types_compatible_p T (array U 2)))',
            self::transform('int r = __builtin_types_compatible_p(T, U[2]);'),
        );

        $arguments = self::findArguments('int r = __builtin_types_compatible_p(T, x);');

        self::assertInstanceOf(NamedType::class, $arguments[0]);
        self::assertInstanceOf(IdentifierExpression::class, $arguments[1]);
    }

    public function testMemberDesignatorsOfOffsetOfAreKept(): void
    {
        $arguments = self::findArguments('int r = __builtin_offsetof(T, T);');

        self::assertInstanceOf(NamedType::class, $arguments[0]);
        self::assertInstanceOf(IdentifierExpression::class, $arguments[1]);
    }

    /**
     * @return list<mixed>
     */
    private static function findArguments(string $code): array
    {
        $declaration = \array_last(self::transformDeclarations($code));

        self::assertInstanceOf(VariableDeclaration::class, $declaration);
        self::assertInstanceOf(BuiltinCallExpression::class, $declaration->initializer);

        return $declaration->initializer->arguments->items;
    }
}
