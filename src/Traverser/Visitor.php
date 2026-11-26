<?php

declare(strict_types=1);

namespace FFI\Parser\Traverser;

use FFI\Parser\Node\NodeInterface;

/**
 * Visitor that changes nothing, a base for the visitors that need only one of
 * the methods.
 */
abstract class Visitor implements VisitorInterface
{
    public function enter(NodeInterface $node, Context $context): NodeInterface|Action|null
    {
        return null;
    }

    public function leave(NodeInterface $node, Context $context): NodeInterface|Action|null
    {
        return null;
    }
}
