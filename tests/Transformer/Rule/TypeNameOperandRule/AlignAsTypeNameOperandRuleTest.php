<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\AlignAsTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AlignAsTypeNameOperandRule::class)]
final class AlignAsTypeNameOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new AlignAsTypeNameOperandRule();
    }

    public function testExpressionIsTurnedIntoTypeName(): void
    {
        self::assertSame('(variable alignas((array T 3)) int r)', self::transform('alignas(T[3]) int r;'));
    }

    public function testExpressionIsKept(): void
    {
        self::assertDeclarationIsKept('alignas(x[3]) int r;');
    }
}
