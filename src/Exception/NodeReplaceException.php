<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;

/**
 * Occurs when a visitor replaces a node with a value that cannot take its
 * place.
 */
final class NodeReplaceException extends TraverserException
{
    /**
     * @param \Error $reason the failure reported by PHP, such as a type of
     *        the property that does not accept the value
     */
    public static function becauseNodeCannotBeReplaced(NodeInterface $parent, string $property, \Error $reason): self
    {
        $message = \sprintf(
            'The property "%s" of %s cannot hold the value given by a visitor: "%s"',
            \addcslashes($property, '"'),
            $parent::class,
            \addcslashes($reason->getMessage(), '"'),
        );

        return new self($message, $reason);
    }

    public static function becauseRootDeclarationCannotBeReplaced(
        RootDeclarationInterface $declaration,
        NodeInterface $replacement,
    ): self {
        $message = \sprintf(
            'The root declaration %s cannot be replaced by a visitor with %s, which is not a root declaration',
            $declaration::class,
            $replacement::class,
        );

        return new self($message);
    }
}
