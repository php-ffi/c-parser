<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * Prefix operator applied to an operand, such as `-x`, `~x` or `&x`. The
 * operator is the class of the node. The `sizeof` and `alignof` operators
 * are nodes of their own, since they may take a type instead.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.4 Unary operators
 */
abstract class UnaryExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The expression the operator is applied to
         */
        public readonly Expression $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
