<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * Expression in parentheses: `(a + b)`. Keeps the grouping written in the
 * source, the node starts at the opening parenthesis.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.2 Primary expressions
 */
final class ParenExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public ExpressionInterface $expression,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
