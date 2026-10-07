<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsSpecifier;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsTypeSpecifier;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `alignas` of a type name into `alignas` of an expression, when the
 * type is a name declared as other than a type: `alignas(x)` is the
 * alignment the constant `x` gives.
 *
 * @template-extends ExpressionOperandRule<AlignAsTypeSpecifier>
 */
final class AlignAsExpressionOperandRule extends ExpressionOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [AlignAsTypeSpecifier::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $operand = $this->findExpression($node->type, $context);

        if ($operand === null) {
            return null;
        }

        return new AlignAsSpecifier($operand, $node->offset);
    }
}
