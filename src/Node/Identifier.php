<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Name as written in the source: the name a primary expression refers to or
 * the name of a member in a member access. It is not an expression by itself.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.2 Identifiers
 */
final class Identifier extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name as written
         *
         * @var non-empty-string
         */
        public readonly string $name,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
