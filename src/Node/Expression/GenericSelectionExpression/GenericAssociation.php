<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\GenericSelectionExpression;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Node;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Item of a generic selection, a type name and the expression selected for
 * it, `int: 1`, or the default one, `default: 0`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.2.1 Generic selection
 */
final class GenericAssociation extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type name before `:`, {@see null} for the `default` association
         */
        public readonly ?TypeInterface $type,
        /**
         * The expression selected for the type
         */
        public readonly ExpressionInterface $expression,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
