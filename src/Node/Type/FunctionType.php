<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Type\FunctionType\ParameterList;

/**
 * Function type, written by the parameter list of a declarator:
 * `int f(char *s, ...)`. An empty list (`f()`) and the single `void`
 * parameter (`f(void)`) are kept apart, though both mean no parameters in
 * C23.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.4 Function declarators
 */
final class FunctionType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The return type, {@see null} in case of the specifiers give no
         * type, such as in `static (f)(void);`
         */
        public readonly ?TypeInterface $returnType,
        /**
         * The parameters in the order written, empty in case of `f()` and
         * `f(...)`
         */
        public readonly ParameterList $parameters = new ParameterList([]),
        /**
         * Whether the parameters end with `...`
         */
        public readonly bool $isVariadic = false,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
