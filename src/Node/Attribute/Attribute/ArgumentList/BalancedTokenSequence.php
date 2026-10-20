<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute\Attribute\ArgumentList;

use FFI\Parser\Node\Node;

/**
 * Argument of an attribute that is not a constant expression, `introduced=10.4`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13.2 General
 */
final class BalancedTokenSequence extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The tokens exactly as written
         *
         * @var non-empty-string
         */
        public readonly string $raw,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
