<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\AlignOfTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AlignOfTypeNameOperandRule::class)]
final class AlignOfTypeNameOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new AlignOfTypeNameOperandRule();
    }

    public function testExpressionIsTurnedIntoTypeName(): void
    {
        self::assertSame('(variable int r = (alignof (array T 3)))', self::transform('int r = alignof(T[3]);'));
    }

    public function testExpressionIsKept(): void
    {
        self::assertDeclarationIsKept('int r = alignof(x[3]);');
    }
}
