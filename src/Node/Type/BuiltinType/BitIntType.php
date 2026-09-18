<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\BuiltinType;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Type\BuiltinType;

/**
 * Bit-precise integer type, `_BitInt(N)`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 */
final class BitIntType extends BuiltinType
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The constant expression of the width in bits
         */
        public ExpressionInterface $width,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
