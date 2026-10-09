<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\TypeOfUnqualTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TypeOfUnqualTypeNameOperandRule::class)]
final class TypeOfUnqualTypeNameOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new TypeOfUnqualTypeNameOperandRule();
    }

    public function testExpressionIsTurnedIntoTypeName(): void
    {
        self::assertSame('(variable typeof_unqual((fn T ())) r)', self::transform('typeof_unqual(T()) r;'));
    }

    public function testExpressionIsKept(): void
    {
        self::assertDeclarationIsKept('typeof_unqual(x()) r;');
    }
}
