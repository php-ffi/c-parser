<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

use FFI\Parser\Node\NodeInterface;

/**
 * Check of the syntax tree beyond the syntax, such as a constraint of C or
 * a limitation of PHP FFI.
 *
 * The validator does not walk the tree: {@see \FFI\Parser\Validator} gives
 * it every node of the tree, each one once, and the validator picks the
 * kinds of nodes it checks.
 */
interface ValidatorRuleInterface
{
    /**
     * Checks a single node. An empty result means the node breaks no rule
     * of this validator.
     *
     * @return iterable<mixed, Violation>
     */
    public function validate(NodeInterface $node, ValidationContext $context): iterable;
}
