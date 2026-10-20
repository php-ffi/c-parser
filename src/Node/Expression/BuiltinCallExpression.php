<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Expression\BuiltinCallExpression\ArgumentList;
use FFI\Parser\Node\Identifier;

/**
 * Call of a `__builtin_` function with a type name among its arguments:
 * `__builtin_offsetof(struct S, a.b[2])` or `__builtin_va_arg(ap, int)`. A call with
 * expressions only is a {@see CallExpression}.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Other-Builtins.html GCC, Other Built-in Functions
 */
final class BuiltinCallExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public Identifier $name,
        public ArgumentList $arguments,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
