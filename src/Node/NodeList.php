<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Base of the nodes that hold a list of nodes of one kind.
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
         * @var list<TNode>
         */
        public array $items,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }

    /**
     * Returns the index of the node in the list or {@see null} if it is not there.
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

    /**
     * Replaces the node at the index, appends it if the index is {@see null} or equals the count.
     *
     * @param int<0, max>|null $offset
     * @param TNode $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // @phpstan-ignore-next-line the contract already narrows the offset, the assertion checks a caller that ignores it
        \assert($offset === null || (\is_int($offset) && $offset >= 0 && $offset <= \count($this->items)));

        if ($offset === null) {
            $this->items[] = $value;

            return;
        }

        \array_splice($this->items, $offset, 1, [$value]);
    }

    /**
     * Removes the node at the index, the following nodes shift left.
     *
     * @param int<0, max> $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        // @phpstan-ignore-next-line the contract already narrows the offset, the assertion checks a caller that ignores it
        \assert(\is_int($offset) && $offset >= 0);

        \array_splice($this->items, $offset, 1);
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
