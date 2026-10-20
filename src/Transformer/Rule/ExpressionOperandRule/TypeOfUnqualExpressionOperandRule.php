<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\ExpressionOperandRule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\TypeOfUnqualType;
use FFI\Parser\Node\Type\TypeOfUnqualTypeType;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `typeof_unqual` of a type name into `typeof_unqual` of an expression
 * when the name is declared as other than a type: `typeof_unqual(x)`.
 *
 * @template-extends ExpressionOperandRule<TypeOfUnqualTypeType>
 */
final class TypeOfUnqualExpressionOperandRule extends ExpressionOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [TypeOfUnqualTypeType::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $operand = $this->findExpression($node->type, $context);

        if ($operand === null) {
            return null;
        }

        return new TypeOfUnqualType($operand, $node->offset);
    }
}
