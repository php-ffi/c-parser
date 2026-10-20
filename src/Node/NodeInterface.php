<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Node of the syntax tree.
 *
 * @property-read int<0, max> $offset offset of the first byte of the node in the source
 */
interface NodeInterface {}
