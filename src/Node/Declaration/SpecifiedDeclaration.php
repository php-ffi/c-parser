<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\Specifier\SpecifierList;

/**
 * Base of the declarations written with declaration specifiers. The ones
 * that are not a part of the type are kept by a list.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
abstract class SpecifiedDeclaration extends Declaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The storage-class, function and alignment specifiers in the order
         * written
         */
        public readonly SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
