<?php

declare(strict_types=1);

namespace FFI\Parser\Traverser;

use FFI\Parser\Node\NodeInterface;

abstract class Visitor implements VisitorInterface
{
    public function enter(NodeInterface $node, Context $context): NodeInterface|Action|null
    {
        // NOOP: You need an override this method to implement the functionality
        return null;
    }

    public function leave(NodeInterface $node, Context $context): NodeInterface|Action|null
    {
        // NOOP: You need an override this method to implement the functionality
        return null;
    }
}
