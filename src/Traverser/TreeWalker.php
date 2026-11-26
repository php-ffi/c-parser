<?php

declare(strict_types=1);

namespace FFI\Parser\Traverser;

use FFI\Parser\Exception\ChildrenSkipException;
use FFI\Parser\Exception\NodeRemoveException;
use FFI\Parser\Exception\NodeReplaceException;
use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;

/**
 * A single walk of the visitors over the syntax tree.
 *
 * Inspired by the Nikic's PHP Parser
 *
 * @link https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeTraverser.php
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 */
final class TreeWalker
{
    /**
     * @var \SplObjectStorage<NodeInterface, NodeInterface|Action::Remove>
     */
    private \SplObjectStorage $results;

    /**
     * @var array<class-string<NodeInterface>, list<string>>
     */
    private array $properties = [];

    /**
     * Nodes that hold the visited one, from a root declaration to the direct
     * parent.
     *
     * @var list<NodeInterface>
     */
    private array $parents = [];

    public function __construct(
        /**
         * @var non-empty-list<VisitorInterface>
         */
        private readonly array $visitors,
        /**
         * @var list<RootDeclarationInterface>
         */
        private readonly array $declarations,
    ) {
        $this->results = new \SplObjectStorage();
    }

    /**
     * Walks every declaration with every visitor.
     *
     * @return list<RootDeclarationInterface> the declarations with the
     *         replaced ones in their places and without the removed ones
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    public function walk(): array
    {
        try {
            return $this->walkDeclarations();
        } finally {
            $this->results = new \SplObjectStorage();
            $this->parents = [];
        }
    }

    /**
     * @return list<RootDeclarationInterface>
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    private function walkDeclarations(): array
    {
        $result = [];

        foreach ($this->declarations as $declaration) {
            $node = $this->visit($declaration);

            if ($node === Action::Remove) {
                continue;
            }

            if (!$node instanceof RootDeclarationInterface) {
                throw NodeReplaceException::becauseRootDeclarationCannotBeReplaced($declaration, $node);
            }

            $result[] = $node;
        }

        return $result;
    }

    /**
     * @return NodeInterface|Action::Remove
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    private function visit(NodeInterface $node): NodeInterface|Action
    {
        if (isset($this->results[$node])) {
            return $this->results[$node];
        }

        $this->results[$node] = $node;

        return $this->results[$node] = $this->walkNode($node);
    }

    /**
     * @return NodeInterface|Action::Remove
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    private function walkNode(NodeInterface $node): NodeInterface|Action
    {
        $isSkippingChildren = false;

        foreach ($this->visitors as $visitor) {
            $result = $visitor->enter($node, new Context($this->parents, $this->declarations));

            if ($result === Action::Remove) {
                return $result;
            }

            if ($result === Action::SkipChildren) {
                $isSkippingChildren = true;
            } elseif ($result !== null) {
                $node = $result;
            }
        }

        if (!$isSkippingChildren) {
            $this->parents[] = $node;

            try {
                $this->walkChildren($node);
            } finally {
                \array_pop($this->parents);
            }
        }

        return $this->leave($node);
    }

    /**
     * @return NodeInterface|Action::Remove
     * @throws ChildrenSkipException in case of a visitor asks to skip the
     *         children of the node it leaves
     */
    private function leave(NodeInterface $node): NodeInterface|Action
    {
        foreach ($this->visitors as $visitor) {
            $result = $visitor->leave($node, new Context($this->parents, $this->declarations));

            if ($result === Action::Remove) {
                return $result;
            }

            if ($result === Action::SkipChildren) {
                throw ChildrenSkipException::becauseChildrenCannotBeSkippedOnLeave($node);
            }

            if ($result !== null) {
                $node = $result;
            }
        }

        return $node;
    }

    /**
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    private function walkChildren(NodeInterface $node): void
    {
        foreach ($this->getProperties($node) as $property) {
            // @phpstan-ignore-next-line the property name is taken from the public properties of the same node
            $value = $node->{$property};

            if ($value instanceof NodeInterface) {
                $child = $this->visit($value);

                if ($child === Action::Remove) {
                    throw NodeRemoveException::becauseNodeCannotBeRemoved($node, $property);
                }

                if ($child !== $value) {
                    $this->replace($node, $property, $child);
                }
            } elseif (\is_array($value)) {
                $this->walkList($node, $property, $value);
            }
        }
    }

    /**
     * Returns the names of the public properties of the node, the ones of a
     * parent class first.
     *
     * @return list<string>
     */
    private function getProperties(NodeInterface $node): array
    {
        return $this->properties[$node::class] ??= $this->readProperties(new \ReflectionClass($node));
    }

    /**
     * @param \ReflectionClass<object> $class
     * @return list<string>
     */
    private function readProperties(\ReflectionClass $class): array
    {
        $parent = $class->getParentClass();
        $properties = $parent === false ? [] : $this->readProperties($parent);

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && !\in_array($property->name, $properties, true)) {
                $properties[] = $property->name;
            }
        }

        return $properties;
    }

    /**
     * @param array<mixed> $items
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    private function walkList(NodeInterface $node, string $property, array $items): void
    {
        $isChanged = false;

        foreach ($items as $index => $item) {
            if (!$item instanceof NodeInterface) {
                continue;
            }

            $child = $this->visit($item);

            if ($child === $item) {
                continue;
            }

            $isChanged = true;

            if ($child === Action::Remove) {
                unset($items[$index]);
            } else {
                $items[$index] = $child;
            }
        }

        if ($isChanged) {
            $this->replace($node, $property, \array_values($items));
        }
    }

    /**
     * @throws NodeReplaceException in case of the property does not accept
     *         the value
     */
    private function replace(NodeInterface $node, string $property, mixed $value): void
    {
        try {
            // @phpstan-ignore-next-line the property name is taken from the public properties of the same node
            $node->{$property} = $value;
        } catch (\Error $e) {
            throw NodeReplaceException::becauseNodeCannotBeReplaced($node, $property, $e);
        }
    }
}
