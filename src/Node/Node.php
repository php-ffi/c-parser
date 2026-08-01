<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Base of every node of the syntax tree.
 */
abstract class Node
{
    public function __construct(
        /**
         * Offset of the first byte of the node in the source
         *
         * @var int<0, max>
         */
        public readonly int $offset = 0,
    ) {}
}
