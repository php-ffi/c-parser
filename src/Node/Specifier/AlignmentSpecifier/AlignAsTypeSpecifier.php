<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier\AlignmentSpecifier;

use FFI\Parser\Node\Specifier\AlignmentSpecifier;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Alignment of a type, `alignas(double)`. A lone identifier in the
 * parentheses is read as a type name, even if it names an enumeration
 * constant.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.6 Alignment specifier
 */
final class AlignAsTypeSpecifier extends AlignmentSpecifier
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type whose alignment is taken
         */
        public TypeInterface $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
