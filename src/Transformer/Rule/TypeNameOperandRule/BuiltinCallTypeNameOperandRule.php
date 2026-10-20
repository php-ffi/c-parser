<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Node\Expression\BuiltinCallExpression;
use FFI\Parser\Node\Expression\BuiltinCallExpression\ArgumentList;
use FFI\Parser\Node\Expression\CallExpression;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\TransformationContext;

/**
 * Turns the arguments of a `__builtin_` function that start with a typedef
 * name into type names: in `__builtin_types_compatible_p(T, U[2])` the call
 * is a {@see BuiltinCallExpression}. The members of `__builtin_offsetof` are
 * not converted.
 *
 * @template-extends TypeNameOperandRule<CallExpression|BuiltinCallExpression>
 */
final class BuiltinCallTypeNameOperandRule extends TypeNameOperandRule
{
    public function getNodeClasses(): iterable
    {
        return [CallExpression::class, BuiltinCallExpression::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $name = $this->findName($node);

        if ($name === null) {
            return null;
        }

        $arguments = $node->arguments->items;
        $isChanged = false;

        foreach ($arguments as $index => $argument) {
            if ($index > 0 && $name->value === '__builtin_offsetof') {
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
            $name,
            new ArgumentList($arguments, $node->arguments->offset),
            $node->offset,
        );
    }

    /**
     * @return Identifier|null the name of the built-in function, {@see null}
     *         for other calls
     */
    private function findName(CallExpression|BuiltinCallExpression $node): ?Identifier
    {
        if ($node instanceof BuiltinCallExpression) {
            return $node->name;
        }

        if (!$node->function instanceof IdentifierExpression
            || !\str_starts_with($node->function->name->value, '__builtin_')) {
            return null;
        }

        return $node->function->name;
    }
}
