<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Expression\GenericSelectionExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns the controlling expression of `_Generic` into a type name, when
 * the expression starts with a typedef name: `_Generic(T, ...)` selects by
 * the type `T`.
 */
final class GenericSelectionTypeNameOperandRule extends TypeNameOperandRule
{
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node instanceof GenericSelectionExpression || !$node->operand instanceof ExpressionInterface) {
            return null;
        }

        $type = $this->findTypeName($node->operand, $context);

        if ($type === null) {
            return null;
        }

        return new GenericSelectionExpression($type, $node->associations, $node->offset);
    }
}
