<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\BuiltinType;

use FFI\Parser\Node\Type\BuiltinType;

/**
 * The `_FloatNx` binary extended floating type, such as `_Float32x`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H.2.4 Extended floating types
 */
final class FloatNxType extends BuiltinType
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The width N in bits as written
         *
         * @var int<1, max>
         */
        public readonly int $width,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
