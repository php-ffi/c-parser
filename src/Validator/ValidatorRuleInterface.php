<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

use FFI\Parser\Node\NodeInterface;

/**
 * Check of a node of the syntax tree beyond the syntax.
 *
 * @template TNode of NodeInterface
 */
interface ValidatorRuleInterface
{
    /**
     * Lists the node classes or interfaces the validator checks.
     *
     * @return iterable<mixed, class-string<TNode>>
     */
    public function getNodeClasses(): iterable;

    /**
     * @param TNode $node
     * @return iterable<mixed, Violation> empty when the node breaks no rule
     */
    public function validate(NodeInterface $node, ValidationContext $context): iterable;
}
