<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\Expression\AlignOfExpression;
use FFI\Parser\Node\Expression\AlignOfTypeExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `alignof` of a type name into `alignof` of an expression when the
 * name is declared as other than a type: `alignof(x)` is `alignof (x)`.
 *
 * The offset of the parenthesis is not kept, the expression has the offset of
 * the name.
 *
 * @template-extends ExpressionOperandRule<AlignOfTypeExpression>
 */
final class AlignOfExpressionOperandRule extends ExpressionOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [AlignOfTypeExpression::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $operand = $this->findExpression($node->type, $context);

        if ($operand === null) {
            return null;
        }

        return new AlignOfExpression(new ParenExpression($operand, $operand->offset), $node->offset);
    }
}
