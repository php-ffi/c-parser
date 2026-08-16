<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * The `alignof` operator applied to an expression: `__alignof__ x` or
 * `__alignof__(x[0])`. C allows only a type name, this is an extension of
 * GCC, accepted in every spelling of the operator (`alignof`, `_Alignof`,
 * `__alignof__`, `__alignof`).
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Alignment.html GCC, Alignment
 */
final class AlignOfExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The expression whose alignment is taken
         */
        public readonly ExpressionInterface $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
