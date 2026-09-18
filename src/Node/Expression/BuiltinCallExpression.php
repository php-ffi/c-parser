<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Expression\BuiltinCallExpression\ArgumentList;
use FFI\Parser\Node\Identifier;

/**
 * Call of a built-in function of a compiler, a name that starts with
 * `__builtin_`: `__builtin_offsetof(struct S, a.b[2])`, what the `offsetof`
 * macro of `<stddef.h>` expands to, or `__builtin_types_compatible_p(int, T)`.
 * The meaning of the function and of its arguments is up to the consumer.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Other-Builtins.html GCC, Other Built-in Functions
 */
final class BuiltinCallExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the function, `__builtin_offsetof`
         */
        public Identifier $name,
        /**
         * The arguments in parentheses
         */
        public ArgumentList $arguments,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
