<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\Expression\BuiltinCallExpression;
use FFI\Parser\Node\Expression\BuiltinCallExpression\ArgumentList;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns the arguments of a built-in function into type names, when they
 * start with a typedef name: in `__builtin_types_compatible_p(T, U[2])`
 * both arguments are types.
 *
 * The second and the following arguments of `__builtin_offsetof`
 * designate members and are not converted.
 *
 * @template-extends TypeNameOperandRule<BuiltinCallExpression>
 */
final class BuiltinCallTypeNameOperandRule extends TypeNameOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [BuiltinCallExpression::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $arguments = $node->arguments->items;
        $isChanged = false;

        foreach ($arguments as $index => $argument) {
            if ($index > 0 && $node->name->value === '__builtin_offsetof') {
                break;
            }

            if (!$argument instanceof ExpressionInterface) {
                continue;
            }

            $type = $this->findTypeName($argument, $context);

            if ($type === null) {
                continue;
            }

            $arguments[$index] = $type;
            $isChanged = true;
        }

        if (!$isChanged) {
            return null;
        }

        return new BuiltinCallExpression(
            $node->name,
            new ArgumentList($arguments, $node->arguments->offset),
            $node->offset,
        );
    }
}
