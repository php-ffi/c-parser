<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Type\TypeInterface;

/**
 * Conversion of an operand to a type: `(T)x`.
 *
 * Without a table of the declared names, `(T)` is a cast only when the next
 * token can start an operand but cannot continue a parenthesized expression,
 * so `(T)-x`, `(T)*p`, `(T)[0]` and `(T).m` are a parenthesized operand.
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
        public readonly TypeInterface $type,
        /**
         * The expression being converted
         */
        public readonly ExpressionInterface $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
