<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * The `sizeof` operator applied to an expression: `sizeof x`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.4.4 The sizeof and alignof operators
 */
final class SizeofExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The expression whose size is taken
         */
        public readonly Expression $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
