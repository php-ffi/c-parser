<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Expression\GenericSelectionExpression\GenericAssociationList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Choice of an expression by the type of the controlling operand:
 * `_Generic(x, int: 1, default: 0)`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.2.1 Generic selection
 */
final class GenericSelectionExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The operand whose type selects the association: an expression, or
         * a type name in GCC, Clang and C2y, `_Generic(int, int: 1)`
         */
        public ExpressionInterface|TypeInterface $operand,
        public GenericAssociationList $associations,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
