<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\TypeOfTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TypeOfTypeNameOperandRule::class)]
final class TypeOfTypeNameOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new TypeOfTypeNameOperandRule();
    }

    public function testExpressionIsTurnedIntoTypeName(): void
    {
        self::assertSame('(variable typeof((fn (ptr T) (U))) r)', self::transform('typeof(T *(U)) r;'));
    }

    public function testExpressionIsKept(): void
    {
        self::assertDeclarationIsKept('typeof(x()) r;');
    }
}
