<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator;

/**
 * Designator of a range of the elements of an array, `[0 ... 3]`, an
 * extension of GCC. Both bounds are included.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Designated-Inits.html GCC, Designated Initializers
 */
final class RangeDesignator extends Designator
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The constant expression of the first index
         */
        public readonly ExpressionInterface $first,
        /**
         * The constant expression of the last index
         */
        public readonly ExpressionInterface $last,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
