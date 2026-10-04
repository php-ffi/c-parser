<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\Expression\AlignOfExpression;
use FFI\Parser\Node\Expression\BinaryExpression;
use FFI\Parser\Node\Expression\BinaryExpression\AddExpression;
use FFI\Parser\Node\Expression\BinaryExpression\BitwiseAndExpression;
use FFI\Parser\Node\Expression\BinaryExpression\BitwiseOrExpression;
use FFI\Parser\Node\Expression\BinaryExpression\BitwiseXorExpression;
use FFI\Parser\Node\Expression\BinaryExpression\DivExpression;
use FFI\Parser\Node\Expression\BinaryExpression\EqualExpression;
use FFI\Parser\Node\Expression\BinaryExpression\GreaterExpression;
use FFI\Parser\Node\Expression\BinaryExpression\GreaterOrEqualExpression;
use FFI\Parser\Node\Expression\BinaryExpression\LessExpression;
use FFI\Parser\Node\Expression\BinaryExpression\LessOrEqualExpression;
use FFI\Parser\Node\Expression\BinaryExpression\LogicalAndExpression;
use FFI\Parser\Node\Expression\BinaryExpression\LogicalOrExpression;
use FFI\Parser\Node\Expression\BinaryExpression\MulExpression;
use FFI\Parser\Node\Expression\BinaryExpression\NotEqualExpression;
use FFI\Parser\Node\Expression\BinaryExpression\RemExpression;
use FFI\Parser\Node\Expression\BinaryExpression\ShiftLeftExpression;
use FFI\Parser\Node\Expression\BinaryExpression\ShiftRightExpression;
use FFI\Parser\Node\Expression\BinaryExpression\SubExpression;
use FFI\Parser\Node\Expression\CastExpression;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Expression\SizeOfExpression;
use FFI\Parser\Node\Expression\UnaryExpression;
use FFI\Parser\Node\Expression\UnaryExpression\AddressOfExpression;
use FFI\Parser\Node\Expression\UnaryExpression\DereferenceExpression;
use FFI\Parser\Node\Expression\UnaryExpression\MinusExpression;
use FFI\Parser\Node\Expression\UnaryExpression\PlusExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Turns a parenthesized typedef name followed by `+`, `-`, `*` or `&` into
 * a cast of the unary expression after it: `(T)-x*y` is the product of the
 * cast `(T)-x` and `y`.
 *
 * The rule rewrites the outermost binary expression of a sequence of
 * binary operators, the whole sequence is rebuilt by the precedence of the
 * operators. The offset of the operator turned into the unary one is not
 * kept in the tree, the unary expression is given the offset of its
 * operand.
 */
final class TypedefCastRule implements TransformerRuleInterface
{
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if (!$node instanceof BinaryExpression || \array_last($context->parents) instanceof BinaryExpression) {
            return null;
        }

        $items = $this->getSequence($node);
        $isChanged = false;

        for ($i = 0; $i + 2 < \count($items);) {
            $operand = $items[$i];
            $operator = $items[$i + 1];
            \assert($operator instanceof BinaryExpression);

            $holder = $this->findPrefixHolder($operand);
            $paren = $holder === null ? $operand : $holder->operand;
            $name = $this->findTypedefName($paren, $context);

            if ($name === null || !$this->isUnaryOperator($operator)) {
                $i += 2;

                continue;
            }

            $cast = new CastExpression($name, $this->createUnary($operator, $items[$i + 2]), $paren->offset);

            if ($holder === null) {
                $items[$i] = $cast;
            } else {
                $holder->operand = $cast;
            }

            /** @var non-empty-list<ExpressionInterface> */
            $items = [...\array_slice($items, 0, $i + 1), ...\array_slice($items, $i + 3)];
            $isChanged = true;
        }

        if (!$isChanged) {
            return null;
        }

        return $this->build($items);
    }

    /**
     * @return non-empty-list<ExpressionInterface> the operands and the
     *         operators of the sequence of binary operators, the operands
     *         at the even positions and the operators at the odd ones
     */
    private function getSequence(ExpressionInterface $expression): array
    {
        if ($expression instanceof BinaryExpression) {
            return [...$this->getSequence($expression->left), $expression, ...$this->getSequence($expression->right)];
        }

        return [$expression];
    }

    /**
     * Returns the innermost of the prefix operators the operand starts
     * with, the casts, the unary operators, `sizeof` and `alignof` of an
     * expression.
     */
    private function findPrefixHolder(
        ExpressionInterface $operand,
    ): UnaryExpression|CastExpression|SizeOfExpression|AlignOfExpression|null {
        $holder = null;

        while ($operand instanceof UnaryExpression
            || $operand instanceof CastExpression
            || $operand instanceof SizeOfExpression
            || $operand instanceof AlignOfExpression) {
            $holder = $operand;
            $operand = $operand->operand;
        }

        return $holder;
    }

    /**
     * @return NamedType|null the typedef name of the parenthesized name,
     *         {@see null} in case of the expression is not a parenthesized
     *         typedef name
     */
    private function findTypedefName(ExpressionInterface $expression, TransformationContext $context): ?NamedType
    {
        if (!$expression instanceof ParenExpression
            || !$expression->expression instanceof IdentifierExpression
            || !$context->isTypedefName($expression->expression->name->value)) {
            return null;
        }

        return new NamedType($expression->expression->name, $expression->expression->offset);
    }

    private function isUnaryOperator(BinaryExpression $operator): bool
    {
        return $operator instanceof AddExpression
            || $operator instanceof SubExpression
            || $operator instanceof MulExpression
            || $operator instanceof BitwiseAndExpression;
    }

    private function createUnary(BinaryExpression $operator, ExpressionInterface $operand): UnaryExpression
    {
        return match (true) {
            $operator instanceof AddExpression => new PlusExpression($operand, $operand->offset),
            $operator instanceof SubExpression => new MinusExpression($operand, $operand->offset),
            $operator instanceof MulExpression => new DereferenceExpression($operand, $operand->offset),
            default => new AddressOfExpression($operand, $operand->offset),
        };
    }

    /**
     * Builds the binary expressions of the sequence by the precedence of
     * the operators, each of them is left-associative: the root is the
     * last operator of the lowest precedence.
     *
     * @param non-empty-list<ExpressionInterface> $items
     */
    private function build(array $items): ExpressionInterface
    {
        $root = null;
        $index = 0;

        for ($i = 1, $count = \count($items); $i < $count; $i += 2) {
            $operator = $items[$i];
            \assert($operator instanceof BinaryExpression);

            if ($root === null || $this->getPrecedence($operator) <= $this->getPrecedence($root)) {
                $root = $operator;
                $index = $i;
            }
        }

        if ($root === null) {
            return $items[0];
        }

        /** @var non-empty-list<ExpressionInterface> */
        $before = \array_slice($items, 0, $index);
        /** @var non-empty-list<ExpressionInterface> */
        $after = \array_slice($items, $index + 1);
        $left = $this->build($before);

        return new ($root::class)($left, $this->build($after), $left->offset);
    }

    /**
     * @return int<0, 10> the precedence of the binary operator, the comma
     *         has the lowest one
     */
    private function getPrecedence(BinaryExpression $operator): int
    {
        return match (true) {
            $operator instanceof MulExpression,
            $operator instanceof DivExpression,
            $operator instanceof RemExpression => 10,
            $operator instanceof AddExpression,
            $operator instanceof SubExpression => 9,
            $operator instanceof ShiftLeftExpression,
            $operator instanceof ShiftRightExpression => 8,
            $operator instanceof LessExpression,
            $operator instanceof GreaterExpression,
            $operator instanceof LessOrEqualExpression,
            $operator instanceof GreaterOrEqualExpression => 7,
            $operator instanceof EqualExpression,
            $operator instanceof NotEqualExpression => 6,
            $operator instanceof BitwiseAndExpression => 5,
            $operator instanceof BitwiseXorExpression => 4,
            $operator instanceof BitwiseOrExpression => 3,
            $operator instanceof LogicalAndExpression => 2,
            $operator instanceof LogicalOrExpression => 1,
            default => 0,
        };
    }
}
