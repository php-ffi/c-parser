<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * Postfix increment or decrement applied to an operand, `a++` or `a--`. The
 * operator is the class of the node. The other postfix operators, the
 * subscript, the call and the member access, are nodes of their own.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.5 Postfix increment and decrement operators
 */
abstract class PostfixExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The expression the operator is applied to
         */
        public ExpressionInterface $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
