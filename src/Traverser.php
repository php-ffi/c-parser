<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Traverser\TreeWalker;
use FFI\Parser\Traverser\VisitorInterface;

/**
 * Walks the syntax tree with the visitors.
 *
 * Every node is visited once, even a shared one, and the result of its visit
 * takes its place in every parent. The visitors are called in the order they
 * are listed, each one is given the node the previous one returned.
 */
final class Traverser
{
    /**
     * @var list<VisitorInterface>
     */
    private readonly array $visitors;

    /**
     * @param iterable<mixed, VisitorInterface> $visitors
     */
    public function __construct(iterable $visitors = [])
    {
        $this->visitors = \is_array($visitors) ? \array_values($visitors) : \iterator_to_array($visitors, false);
    }

    /**
     * @param iterable<mixed, VisitorInterface> $visitors
     * @param list<RootDeclarationInterface> $declarations
     * @return list<RootDeclarationInterface>
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    public static function execute(array $declarations, iterable $visitors): array
    {
        return (new self($visitors))
            ->traverse($declarations);
    }

    /**
     * Walks the declarations and changes the nodes in place.
     *
     * @param list<RootDeclarationInterface> $declarations
     * @return list<RootDeclarationInterface> the declarations with the
     *         replaced ones in their places and without the removed ones
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    public function traverse(array $declarations): array
    {
        if ($this->visitors === []) {
            return $declarations;
        }

        return (new TreeWalker($this->visitors, $declarations))
            ->walk();
    }
}
