<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Non-function type with the attributes a declarator starts with, `int (__attribute__((x)) *p);`.
 * A function type keeps such attributes among its own.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Attribute-Syntax.html GCC, Attribute Syntax
 */
final class AttributedType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public AttributeList $attributes,
        /**
         * The type the attributes apply to, {@see null} if the specifiers give no type
         */
        public ?TypeInterface $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
