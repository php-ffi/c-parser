<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Expression\ExpressionInterface;

/**
 * Type of an expression, `typeof(x + 1)`, which keeps the qualifiers of the
 * operand. The type of a type name is a {@see TypeOfTypeType}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.6 Typeof specifiers
 */
final class TypeOfType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The expression in the parentheses
         */
        public readonly ExpressionInterface $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
