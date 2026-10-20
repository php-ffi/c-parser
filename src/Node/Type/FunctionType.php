<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Type\FunctionType\ParameterList;

/**
 * Function type, written by the parameter list of a declarator, `int f(char *s, ...)`.
 * An empty list `f()` and `f(void)` are kept apart.
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
         * The return type, {@see null} if the specifiers give no type
         */
        public ?TypeInterface $returnType,
        public ParameterList $parameters = new ParameterList([]),
        /**
         * Whether the parameters end with `...`
         */
        public bool $isVariadic = false,
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
