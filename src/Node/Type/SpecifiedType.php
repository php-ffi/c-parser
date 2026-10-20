<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Specifier\SpecifierList;

/**
 * Type name written with alignment specifiers or attributes, `alignas(8) int` in `sizeof(alignas(8) int)`.
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
         * The type of the specifiers, {@see null} if none is written, `alignas(8)`
         */
        public ?TypeInterface $type,
        public SpecifierList $specifiers,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
