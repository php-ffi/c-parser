<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Specifier\SpecifierList;

/**
 * Type name written with alignment specifiers or attributes after the
 * specifiers, such as `alignas(8) int` in `sizeof(alignas(8) int)`. A type
 * name without them is the type itself. The node wraps the type of the
 * specifiers, and the abstract declarator wraps the node: `alignas(8) int *`
 * is a pointer to it.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.8 Type names
 */
final class SpecifiedType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the specifiers, {@see null} in case of no type
         * specifier and no qualifier is written, such as in `alignas(8)`
         */
        public ?TypeInterface $type,
        /**
         * The alignment specifiers and the attributes, in the order written
         */
        public SpecifierList $specifiers,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
