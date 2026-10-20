<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Base of every node of the syntax tree.
 */
abstract class Node implements NodeInterface
{
    public function __construct(
        /**
         * @var int<0, max>
         */
        public readonly int $offset = 0,
    ) {}
}
