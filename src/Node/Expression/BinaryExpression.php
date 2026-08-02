<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * Infix operator applied to two operands, such as `a + b`, `a << b` or
 * `a && b`. The operator is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5 Expressions
 */
abstract class BinaryExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The operand on the left of the operator
         */
        public readonly Expression $left,
        /**
         * The operand on the right of the operator
         */
        public readonly Expression $right,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
