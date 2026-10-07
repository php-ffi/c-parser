<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Expression\SizeOfExpression;
use FFI\Parser\Node\Expression\SizeOfTypeExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `sizeof` of a parenthesized expression into `sizeof` of a type
 * name, when the expression starts with a typedef name: `sizeof(T[3])` is
 * the size of an array, `sizeof(T *(U))` is the size of a function type.
 *
 * @template-extends TypeNameOperandRule<SizeOfExpression>
 */
final class SizeOfTypeNameOperandRule extends TypeNameOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [SizeOfExpression::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node->operand instanceof ParenExpression) {
            return null;
        }

        $type = $this->findTypeName($node->operand->expression, $context);

        if ($type === null) {
            return null;
        }

        return new SizeOfTypeExpression($type, $node->offset);
    }
}
