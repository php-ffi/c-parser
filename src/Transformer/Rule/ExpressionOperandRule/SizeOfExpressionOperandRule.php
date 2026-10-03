<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Expression\SizeOfExpression;
use FFI\Parser\Node\Expression\SizeOfTypeExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `sizeof` of a type name into `sizeof` of an expression, when the
 * type is a name declared as other than a type: `sizeof(x)` is `sizeof (x)`.
 *
 * The offset of the parenthesis is not kept in the tree, the parenthesized
 * expression is given the offset of the name.
 */
final class SizeOfExpressionOperandRule extends ExpressionOperandRule
{
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node instanceof SizeOfTypeExpression) {
            return null;
        }

        $operand = $this->findExpression($node->type, $context);

        if ($operand === null) {
            return null;
        }

        return new SizeOfExpression(new ParenExpression($operand, $operand->offset), $node->offset);
    }
}
