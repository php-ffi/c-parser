<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Node of the syntax tree. The kinds of nodes that cut across the classes,
 * such as the declarations written at the root of the file, are interfaces
 * that extend this one.
 *
 * @property-read int<0, max> $offset offset of the first byte of the node in the source
 */
interface NodeInterface {}
