<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator;

/**
 * Designator of an element of an array, `[2]`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 */
final class IndexDesignator extends Designator
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The constant expression of the index
         */
        public readonly ExpressionInterface $index,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
