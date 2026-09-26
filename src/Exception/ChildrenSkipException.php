<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use FFI\Parser\Node\NodeInterface;

/**
 * Occurs when a visitor asks to skip the children of a node that are
 * already walked.
 */
final class ChildrenSkipException extends TraverserException
{
    public static function becauseChildrenCannotBeSkippedOnLeave(NodeInterface $node): self
    {
        $message = \sprintf(
            'The children of %s cannot be skipped by a visitor that leaves the node, they are already walked',
            $node::class,
        );

        return new self($message);
    }
}
