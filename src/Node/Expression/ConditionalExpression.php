<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * The conditional operator: `condition ? then : else`, and the GCC one with
 * the middle operand omitted, `condition ?: else`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.16 Conditional operator
 * @link https://gcc.gnu.org/onlinedocs/gcc/Conditionals.html GCC, Conditionals with Omitted Operands
 */
final class ConditionalExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The expression before `?`
         */
        public ExpressionInterface $condition,
        /**
         * The expression between `?` and `:`, {@see null} in case of it is
         * omitted, `a ?: b`, the value of the condition is then the result
         */
        public ?ExpressionInterface $then,
        public ExpressionInterface $else,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
