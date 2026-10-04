<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\TypeOfType;
use FFI\Parser\Node\Type\TypeOfTypeType;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `typeof` of an expression into `typeof` of a type name, when the
 * expression starts with a typedef name: `typeof(T())` and `typeof(T *(U))`
 * are function types.
 */
final class TypeOfTypeNameOperandRule extends TypeNameOperandRule
{
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node instanceof TypeOfType) {
            return null;
        }

        $type = $this->findTypeName($node->operand, $context);

        if ($type === null) {
            return null;
        }

        return new TypeOfTypeType($type, $node->offset);
    }
}
