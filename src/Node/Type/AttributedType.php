<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Type with the attributes written at the start of a parenthesized
 * declarator, `void (__stdcall *f)(int)`. As in GCC, they apply to the type
 * the parenthesized declarator receives, the function in this example,
 * which may be any type. The attributes of a pointer, an array or a
 * function written after it are a property of that type. The node starts
 * where the attributes do.
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
         * specifiers give no type, such as in `static (__stdcall *f)(void);`
         */
        public ?TypeInterface $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
