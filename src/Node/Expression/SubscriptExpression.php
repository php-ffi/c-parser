<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * Array subscript: `array[index]`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.2 Array subscripting
 */
final class SubscriptExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public ExpressionInterface $array,
        public ExpressionInterface $index,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
