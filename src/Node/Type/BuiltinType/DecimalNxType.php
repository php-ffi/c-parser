<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\BuiltinType;

use FFI\Parser\Node\Type\BuiltinType;

/**
 * The `_DecimalNx` decimal extended floating type, such as `_Decimal64x`
 * or `_Decimal128x`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H.2.4 Extended floating types
 */
final class DecimalNxType extends BuiltinType
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The width N in bits as written: `64` in `_Decimal64x`
         *
         * @var int<1, max>
         */
        public readonly int $width,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
