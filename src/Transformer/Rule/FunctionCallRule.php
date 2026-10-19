<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\Expression\BinaryExpression;
use FFI\Parser\Node\Expression\BinaryExpression\CommaExpression;
use FFI\Parser\Node\Expression\CallExpression;
use FFI\Parser\Node\Expression\CallExpression\ArgumentList;
use FFI\Parser\Node\Expression\CastExpression;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\MemberExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Expression\PostfixExpression;
use FFI\Parser\Node\Expression\SubscriptExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Turns a cast to a parenthesized name declared as other than a type into
 * a call of it: `(f)(x)` is the call `f(x)`, `(f)(x, y)[0]` is the call
 * `f(x, y)` with the subscript applied to its result.
 *
 * @template-implements TransformerRuleInterface<CastExpression>
 */
final class FunctionCallRule implements TransformerRuleInterface
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

        $function = new ParenExpression(
            new IdentifierExpression($node->type->name, $node->type->offset),
            $node->offset,
        );

        return $this->findCall($function, $node->operand);
    }

    /**
     * Applies the parenthesized operand that starts the expression to the
     * function as its arguments, the operators applied to the
     * operand are applied to the call, a cast to a name the expression
     * starts with is a call as well.
     *
     * @return ExpressionInterface|null the expression with the call,
     *         {@see null} in case of the expression does not start with a
     *         parenthesized operand
     */
    private function findCall(ExpressionInterface $function, ExpressionInterface $expression): ?ExpressionInterface
    {
        if ($expression instanceof ParenExpression) {
            $arguments = $this->getArguments($expression->expression);

            return new CallExpression(
                $function,
                new ArgumentList($arguments, $arguments[0]->offset),
                $function->offset,
            );
        }

        if ($expression instanceof CastExpression && $expression->type instanceof NamedType) {
            $argument = new IdentifierExpression($expression->type->name, $expression->type->offset);
            $call = new CallExpression($function, new ArgumentList([$argument], $argument->offset), $function->offset);

            return $this->findCall($call, $expression->operand);
        }

        if ($expression instanceof SubscriptExpression) {
            $array = $this->findCall($function, $expression->array);

            if ($array === null) {
                return null;
            }

            return new SubscriptExpression($array, $expression->index, $array->offset);
        }

        if ($expression instanceof CallExpression) {
            $callee = $this->findCall($function, $expression->function);

            if ($callee === null) {
                return null;
            }

            return new CallExpression($callee, $expression->arguments, $callee->offset);
        }

        if ($expression instanceof MemberExpression) {
            $object = $this->findCall($function, $expression->object);

            if ($object === null) {
                return null;
            }

            return new ($expression::class)($object, $expression->member, $object->offset);
        }

        if ($expression instanceof PostfixExpression) {
            $operand = $this->findCall($function, $expression->operand);

            if ($operand === null) {
                return null;
            }

            return new ($expression::class)($operand, $operand->offset);
        }

        if ($expression instanceof BinaryExpression) {
            $left = $this->findCall($function, $expression->left);

            if ($left === null) {
                return null;
            }

            return new ($expression::class)($left, $expression->right, $left->offset);
        }

        return null;
    }

    /**
     * @return non-empty-list<ExpressionInterface> the operands of the comma
     *         operators of the expression, the expression itself in case of
     *         it is not a comma operator
     */
    private function getArguments(ExpressionInterface $expression): array
    {
        if ($expression instanceof CommaExpression) {
            return [...$this->getArguments($expression->left), ...$this->getArguments($expression->right)];
        }

        return [$expression];
    }
}
