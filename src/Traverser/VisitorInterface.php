<?php

declare(strict_types=1);

namespace FFI\Parser\Traverser;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Traverser;

/**
 * Step of the walk over the syntax tree that reads, changes or replaces
 * its nodes.
 *
 * The visitor does not walk the tree: {@see Traverser} enters every node once,
 * then its children, then leaves the node. Each method returns {@see null} to
 * keep the node, a node to replace it, or an {@see Action}.
 */
interface VisitorInterface
{
    /**
     * Called before the children of the node are walked. The children of a
     * node returned in place of the given one are walked.
     */
    public function enter(NodeInterface $node, Context $context): NodeInterface|Action|null;

    /**
     * Called after the children of the node are walked. A node returned in
     * place of the given one is not walked.
     */
    public function leave(NodeInterface $node, Context $context): NodeInterface|Action|null;
}
