<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Base of the nodes that hold a list of nodes of one kind, such as the
 * members of a structure. The list starts where its first node does.
 *
 * @template TNode of NodeInterface
 *
 * @template-implements \IteratorAggregate<int<0, max>, TNode>
 * @template-implements \ArrayAccess<int<0, max>, TNode>
 */
abstract class NodeList extends Node implements
    \IteratorAggregate,
    \ArrayAccess,
    \Countable
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The nodes in the order written
         *
         * @var list<TNode>
         */
        public readonly array $items,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }

    /**
     * Returns the ordinal number (position) of an element {@see TNode} in
     * a node list, starting with index 0.
     *
     * Returns {@see null} if the element {@see TNode} does not belong
     * to the node list.
     *
     * @param TNode $node
     * @return int<0, max>|null
     */
    public function findIndex(object $node): ?int
    {
        $index = \array_search($node, $this->items, true);

        if (\is_int($index)) {
            return $index;
        }

        return null;
    }

    /**
     * @return TNode|null
     */
    public function first(): ?NodeInterface
    {
        $first = \array_first($this->items);

        return $first instanceof NodeInterface ? $first : null;
    }

    /**
     * @return TNode|null
     */
    public function last(): ?NodeInterface
    {
        $last = \array_last($this->items);

        return $last instanceof NodeInterface ? $last : null;
    }

    public function offsetExists(mixed $offset): bool
    {
        // @phpstan-ignore-next-line the contract already narrows the offset, the assertion checks a caller that ignores it
        \assert(\is_int($offset) && $offset >= 0);

        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): ?NodeInterface
    {
        // @phpstan-ignore-next-line the contract already narrows the offset, the assertion checks a caller that ignores it
        \assert(\is_int($offset) && $offset >= 0);

        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('Cannot modify readonly ' . static::class);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('Cannot modify readonly ' . static::class);
    }

    /**
     * @return \Traversable<int<0, max>, TNode>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return \count($this->items);
    }
}
