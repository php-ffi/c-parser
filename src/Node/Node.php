<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Base of every node of the syntax tree.
 */
abstract class Node
{
    /**
     * @param int<0, max> $offset offset of the first byte of the node in
     *        the source
     */
    public function __construct(
        public readonly int $offset,
    ) {}
}
