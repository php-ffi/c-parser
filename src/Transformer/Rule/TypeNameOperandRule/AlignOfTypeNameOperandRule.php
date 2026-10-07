<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\Expression\AlignOfExpression;
use FFI\Parser\Node\Expression\AlignOfTypeExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns `alignof` of a parenthesized expression into `alignof` of a type
 * name, when the expression starts with a typedef name: `alignof(T[3])` is
 * the alignment of an array.
 *
 * @template-extends TypeNameOperandRule<AlignOfExpression>
 */
final class AlignOfTypeNameOperandRule extends TypeNameOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [AlignOfExpression::class];
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

        return new AlignOfTypeExpression($type, $node->offset);
    }
}
