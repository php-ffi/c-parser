<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\Specifier\SpecifierList;

/**
 * Base of the declarations written with declaration specifiers.
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
         * Specifiers outside the type and the attributes among them, {@see null} if none is written
         */
        public ?SpecifierList $specifiers = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
