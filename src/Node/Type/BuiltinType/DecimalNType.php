<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\BuiltinType;

use FFI\Parser\Node\Type\BuiltinType;

/**
 * The `_DecimalN` decimal floating type: `_Decimal32`, `_Decimal64` and
 * `_Decimal128`, and the interchange floating types of Annex H such as
 * `_Decimal96`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H.2.2 Interchange floating types
 */
final class DecimalNType extends BuiltinType
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The width N in bits as written: `32` in `_Decimal32`
         *
         * @var int<1, max>
         */
        public readonly int $width,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
