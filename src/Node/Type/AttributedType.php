<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Type other than a function with the attributes a declarator starts with:
 * the GCC attributes at the start of a parenthesized declarator,
 * `int (__attribute__((x)) *p);`, and the MSVC calling conventions,
 * `void g(int * __cdecl);`. A function type keeps such attributes among
 * its own ones, `void (__attribute__((x)) *f)(int)` is a pointer to a
 * function with the attribute. The node starts where the attributes do.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Attribute-Syntax.html GCC, Attribute Syntax
 */
final class AttributedType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The attributes in the order written
         */
        public AttributeList $attributes,
        /**
         * The type the attributes apply to, {@see null} in case of the
         * specifiers give no type, such as in
         * `static (__attribute__((x)) *f)(void);`
         */
        public ?TypeInterface $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
