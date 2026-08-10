<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier\AlignmentSpecifier;

use FFI\Parser\Node\Expression\Expression;
use FFI\Parser\Node\Specifier\AlignmentSpecifier;

/**
 * Alignment given by a constant expression, `alignas(16)`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.6 Alignment specifier
 */
final class AlignAsSpecifier extends AlignmentSpecifier
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The constant expression of the alignment
         */
        public readonly Expression $operand,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
