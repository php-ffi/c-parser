<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer;

use FFI\Parser\Node\NodeInterface;

/**
 * Rewrite of the syntax tree, such as a fix of a construct the grammar
 * cannot recognise without the names declared before it.
 *
 * The transformer does not walk the tree: {@see \FFI\Parser\Transformer}
 * gives it every node of the kinds it lists, each one once and after the
 * children of the node.
 *
 * @template TNode of NodeInterface
 */
interface TransformerRuleInterface
{
    /**
     * Lists the kinds of nodes the transformer rewrites: classes or
     * interfaces of nodes, a node is given to the transformer when it is an
     * instance of one of them.
     *
     * @return iterable<mixed, class-string<TNode>>
     */
    public function getNodeClasses(): iterable;

    /**
     * Rewrites a single node, whose children are already rewritten.
     *
     * @param TNode $node
     * @return NodeInterface|null the node that takes the place of the given
     *         one in every parent, {@see null} in case of the node is kept
     */
    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface;
}
