<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Expression\CallExpression\ArgumentList;

/**
 * Function call: `f(x, y)`.
 *
 * `f()` and `f(x)` where an expression and a type name are both allowed
 * (`sizeof`, `typeof`) are a call, not a function type.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.3 Function calls
 */
final class CallExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public ExpressionInterface $function,
        public ArgumentList $arguments,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
