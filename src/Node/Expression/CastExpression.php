<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Type\TypeInterface;

/**
 * Conversion of an operand to a type: `(T)x`.
 *
 * `(T)` is a cast when the next token can start an operand: `(T)-x` and
 * `(T)*p` are casts, `(T)[0]` and `(T).m` are a parenthesized operand.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.5 Cast operators
 */
final class CastExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type the operand is converted to
         */
        public TypeInterface $type,
        /**
         * The expression being converted
         */
        public ExpressionInterface $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
