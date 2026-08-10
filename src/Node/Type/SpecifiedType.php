<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Specifier\SpecifierList;

/**
 * Type name written with specifiers that are not a part of the type, such
 * as `alignas(8) int` in `sizeof(alignas(8) int)`. C allows none of them
 * in a type name, but the parser accepts them, as Clang does, and keeps
 * them for a validator. A type name without them is the type itself.
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
        public readonly ?Type $type,
        /**
         * The storage-class, function and alignment specifiers in the order
         * written
         */
        public readonly SpecifierList $specifiers,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
