<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsSpecifier;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsTypeSpecifier;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `alignas` of an expression into `alignas` of a type name, when the
 * expression starts with a typedef name: `alignas(T[3])` is the alignment
 * of an array.
 */
final class AlignAsTypeNameOperandRule extends TypeNameOperandRule
{
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node instanceof AlignAsSpecifier) {
            return null;
        }

        $type = $this->findTypeName($node->operand, $context);

        if ($type === null) {
            return null;
        }

        return new AlignAsTypeSpecifier($type, $node->offset);
    }
}
