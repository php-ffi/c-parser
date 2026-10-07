<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\TypeOfUnqualType;
use FFI\Parser\Node\Type\TypeOfUnqualTypeType;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `typeof_unqual` of an expression into `typeof_unqual` of a type
 * name, when the expression starts with a typedef name:
 * `typeof_unqual(T())` is a function type.
 *
 * @template-extends TypeNameOperandRule<TypeOfUnqualType>
 */
final class TypeOfUnqualTypeNameOperandRule extends TypeNameOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [TypeOfUnqualType::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $type = $this->findTypeName($node->operand, $context);

        if ($type === null) {
            return null;
        }

        return new TypeOfUnqualTypeType($type, $node->offset);
    }
}
