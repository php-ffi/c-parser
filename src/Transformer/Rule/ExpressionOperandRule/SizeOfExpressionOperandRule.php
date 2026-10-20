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
 * Turns `sizeof` of a type name into `sizeof` of an expression when the name
 * is declared as other than a type: `sizeof(x)` is `sizeof (x)`.
 *
 * The offset of the parenthesis is not kept, the expression has the offset of
 * the name.
 *
 * @template-extends ExpressionOperandRule<SizeOfTypeExpression>
 */
final class SizeOfExpressionOperandRule extends ExpressionOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [SizeOfTypeExpression::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $operand = $this->findExpression($node->type, $context);

        if ($operand === null) {
            return null;
        }

        return new SizeOfExpression(new ParenExpression($operand, $operand->offset), $node->offset);
    }
}
