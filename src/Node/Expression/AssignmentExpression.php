<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * Assignment of the right operand to the left one, simple `a = b` or
 * compound `a += b`. The operator is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.17 Assignment operators
 */
abstract class AssignmentExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The operand the value is assigned to
         */
        public ExpressionInterface $left,
        /**
         * The assigned value
         */
        public ExpressionInterface $right,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
