<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\TypeOfType;
use FFI\Parser\Node\Type\TypeOfTypeType;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `typeof` of a type name into `typeof` of an expression, when the
 * type is a name declared as other than a type: `typeof(x)` is the type of
 * the object `x`.
 *
 * @template-extends ExpressionOperandRule<TypeOfTypeType>
 */
final class TypeOfExpressionOperandRule extends ExpressionOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [TypeOfTypeType::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $operand = $this->findExpression($node->type, $context);

        if ($operand === null) {
            return null;
        }

        return new TypeOfType($operand, $node->offset);
    }
}
