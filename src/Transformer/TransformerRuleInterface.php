<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer;

use FFI\Parser\Node\NodeInterface;

/**
 * Rewrite of a node of the syntax tree, given after its children.
 *
 * @template TNode of NodeInterface
 */
interface TransformerRuleInterface
{
    /**
     * Lists the node classes or interfaces the transformer accepts.
     *
     * @return iterable<mixed, class-string<TNode>>
     */
    public function getNodeClasses(): iterable;

    /**
     * @param TNode $node
     * @return NodeInterface|null the replacement of the node, {@see null} to keep it
     */
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface;
}
