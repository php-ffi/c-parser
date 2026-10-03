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
 * Turns `alignof` of a type name into `alignof` of an expression, when the
 * type is a name declared as other than a type: `alignof(x)` is
 * `alignof (x)`.
 *
 * The offset of the parenthesis is not kept in the tree, the parenthesized
 * expression is given the offset of the name.
 */
final class AlignOfExpressionOperandRule extends ExpressionOperandRule
{
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node instanceof AlignOfTypeExpression) {
            return null;
        }

        $operand = $this->findExpression($node->type, $context);

        if ($operand === null) {
            return null;
        }

        return new AlignOfExpression(new ParenExpression($operand, $operand->offset), $node->offset);
    }
}
