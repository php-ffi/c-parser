<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use FFI\Parser\Node\NodeInterface;

/**
 * Occurs when a visitor removes a node from a place that cannot be empty.
 */
final class NodeRemoveException extends TraverserException
{
    public static function becauseNodeCannotBeRemoved(NodeInterface $parent, string $property): self
    {
        $message = \sprintf(
            'The node in the property "%s" of %s cannot be removed by a visitor, the property is not a list',
            \addcslashes($property, '"'),
            $parent::class,
        );

        return new self($message);
    }
}
