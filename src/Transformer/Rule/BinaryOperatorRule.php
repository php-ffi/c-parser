<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\Expression\BinaryExpression\AddExpression;
use FFI\Parser\Node\Expression\BinaryExpression\BitwiseAndExpression;
use FFI\Parser\Node\Expression\BinaryExpression\MulExpression;
use FFI\Parser\Node\Expression\BinaryExpression\SubExpression;
use FFI\Parser\Node\Expression\CastExpression;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Expression\PostfixExpression\PostDecrementExpression;
use FFI\Parser\Node\Expression\PostfixExpression\PostIncrementExpression;
use FFI\Parser\Node\Expression\UnaryExpression\AddressOfExpression;
use FFI\Parser\Node\Expression\UnaryExpression\DereferenceExpression;
use FFI\Parser\Node\Expression\UnaryExpression\MinusExpression;
use FFI\Parser\Node\Expression\UnaryExpression\PlusExpression;
use FFI\Parser\Node\Expression\UnaryExpression\PreDecrementExpression;
use FFI\Parser\Node\Expression\UnaryExpression\PreIncrementExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Turns a cast to a parenthesized name declared as other than a type,
 * followed by `+`, `-`, `*` or `&`, into the binary operator: `(x)-y` is
 * the subtraction of `y` from `x`. A `++` or `--` between them is the
 * postfix operator of the name: `(x)++ + y` is the sum of `(x)++` and `y`.
 *
 * The right operand is the operand of the unary operator, the operators
 * around the cast are rearranged by {@see OperatorPrecedenceRule}.
 *
 * @template-implements TransformerRuleInterface<CastExpression>
 */
final class BinaryOperatorRule implements TransformerRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [CastExpression::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node->type instanceof NamedType || !$context->isNonTypeName($node->type->name->value)) {
            return null;
        }

        $left = new ParenExpression(
            new IdentifierExpression($node->type->name, $node->type->offset),
            $node->offset,
        );
        $unary = $node->operand;

        if ($unary instanceof PreIncrementExpression) {
            $left = new PostIncrementExpression($left, $left->offset);
            $unary = $unary->operand;
        } elseif ($unary instanceof PreDecrementExpression) {
            $left = new PostDecrementExpression($left, $left->offset);
            $unary = $unary->operand;
        }

        return match (true) {
            $unary instanceof PlusExpression => new AddExpression($left, $unary->operand, $left->offset),
            $unary instanceof MinusExpression => new SubExpression($left, $unary->operand, $left->offset),
            $unary instanceof DereferenceExpression => new MulExpression($left, $unary->operand, $left->offset),
            $unary instanceof AddressOfExpression => new BitwiseAndExpression($left, $unary->operand, $left->offset),
            default => null,
        };
    }
}
