<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

use FFI\Parser\Node\NodeInterface;

/**
 * Check of the syntax tree beyond the syntax, such as a constraint of C or
 * a limitation of PHP FFI.
 *
 * The validator does not walk the tree: {@see \FFI\Parser\Validator} gives
 * it every node of the kinds it lists, each one once.
 *
 * @template TNode of NodeInterface
 */
interface ValidatorRuleInterface
{
    /**
     * Lists the kinds of nodes the validator checks: classes or interfaces
     * of nodes, a node is given to the validator when it is an instance of
     * one of them.
     *
     * @return iterable<mixed, class-string<TNode>>
     */
    public function getNodeClasses(): iterable;

    /**
     * Checks a single node. An empty result means the node breaks no rule
     * of this validator.
     *
     * @param TNode $node
     * @return iterable<mixed, Violation>
     */
    public function validate(NodeInterface $node, ValidationContext $context): iterable;
}
