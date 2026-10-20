<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * GCC: the `alignof` operator applied to an expression, `__alignof__ x`. C allows only a type name.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Alignment.html GCC, Alignment
 */
final class AlignOfExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public ExpressionInterface $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
