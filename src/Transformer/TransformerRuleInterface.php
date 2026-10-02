<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer;

use FFI\Parser\Node\NodeInterface;

/**
 * Rewrite of the syntax tree, such as a fix of a construct the grammar
 * cannot recognise without the names declared before it.
 *
 * The transformer does not walk the tree: {@see \FFI\Parser\Transformer}
 * gives it every node of the tree, each one once and after the children of
 * the node, and the transformer picks the kinds of nodes it rewrites.
 */
interface TransformerRuleInterface
{
    /**
     * Rewrites a single node, whose children are already rewritten.
     *
     * @return NodeInterface|null the node that takes the place of the given
     *         one in every parent, {@see null} in case of the node is kept
     */
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface;
}
