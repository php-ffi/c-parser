<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * The conditional operator: `condition ? then : else`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.16 Conditional operator
 */
final class ConditionalExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The expression before `?`
         */
        public readonly Expression $condition,
        /**
         * The expression between `?` and `:`
         */
        public readonly Expression $then,
        /**
         * The expression after `:`
         */
        public readonly Expression $else,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
