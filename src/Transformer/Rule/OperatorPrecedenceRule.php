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
use FFI\Parser\Node\Expression\SizeOfExpression;
use FFI\Parser\Node\Expression\UnaryExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Rearranges the operators of an expression by their precedence: `-(x)-y`
 * is the subtraction of `y` from `-(x)`, not the negation of `(x)-y`.
 *
 * @template-implements TransformerRuleInterface<
 *     BinaryExpression|UnaryExpression|CastExpression|SizeOfExpression|AlignOfExpression
 * >
 */
final class OperatorPrecedenceRule implements TransformerRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [
            BinaryExpression::class,
            UnaryExpression::class,
            CastExpression::class,
            SizeOfExpression::class,
            AlignOfExpression::class,
        ];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        $parent = \array_last($context->parents);

        if ($parent instanceof BinaryExpression || $this->isPrefixOperator($parent) || !$this->isMisplaced($node)) {
            return null;
        }

        return $this->build($this->getSequence($node));
    }

    /**
     * @phpstan-assert-if-true UnaryExpression|CastExpression|SizeOfExpression|AlignOfExpression $node
     */
    private function isPrefixOperator(?NodeInterface $node): bool
    {
        return $node instanceof UnaryExpression
            || $node instanceof CastExpression
            || $node instanceof SizeOfExpression
            || $node instanceof AlignOfExpression;
    }

    private function isMisplaced(ExpressionInterface $expression): bool
    {
        if ($expression instanceof BinaryExpression) {
            $precedence = $this->getPrecedence($expression);

            return $this->isLooser($expression->left, $precedence)
                || $this->isLooser($expression->right, $precedence + 1)
                || $this->isMisplaced($expression->left)
                || $this->isMisplaced($expression->right);
        }

        if ($this->isPrefixOperator($expression)) {
            return $expression->operand instanceof BinaryExpression || $this->isMisplaced($expression->operand);
        }

        return false;
    }

    private function isLooser(ExpressionInterface $operand, int $precedence): bool
    {
        return $operand instanceof BinaryExpression && $this->getPrecedence($operand) < $precedence;
    }

    /**
     * Returns the operands and the operators in their source order.
     *
     * @return non-empty-list<ExpressionInterface> the operands at the even
     *         positions and the operators at the odd ones
     */
    private function getSequence(ExpressionInterface $expression): array
    {
        if ($expression instanceof BinaryExpression) {
            return [...$this->getSequence($expression->left), $expression, ...$this->getSequence($expression->right)];
        }

        if ($this->isPrefixOperator($expression)) {
            $items = $this->getSequence($expression->operand);
            $expression->operand = $items[0];
            $items[0] = $expression;

            return $items;
        }

        return [$expression];
    }

    /**
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
     * @return int<0, 10> the precedence of the operator, the comma is the lowest
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
