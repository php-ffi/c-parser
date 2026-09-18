<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;

/**
 * A single walk of the validators over the syntax tree, along with what the
 * walk has visited and found so far.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 */
final class TreeWalker
{
    /**
     * Identifiers of the visited nodes
     *
     * @var array<int, true>
     */
    private array $visited = [];

    /**
     * @var list<Violation>
     */
    private array $violations = [];

    public function __construct(
        /**
         * @var non-empty-list<ValidatorRuleInterface>
         */
        private readonly array $validators,
        /**
         * @var list<RootDeclarationInterface>
         */
        private readonly array $declarations,
    ) {}

    /**
     * Gives every node of the declarations to every validator.
     *
     * @return list<Violation> violations in the order they are found
     */
    public function walk(): array
    {
        foreach ($this->declarations as $declaration) {
            $this->visit($declaration, []);
        }

        return $this->violations;
    }

    /**
     * @param list<NodeInterface> $parents
     */
    private function visit(NodeInterface $node, array $parents): void
    {
        if ($this->wasVisited($node)) {
            return;
        }

        $this->markVisited($node);
        $this->validate($node, new ValidationContext($parents, $this->declarations));

        $parents[] = $node;

        foreach ($this->getChildren($node) as $child) {
            $this->visit($child, $parents);
        }
    }

    private function wasVisited(NodeInterface $node): bool
    {
        return isset($this->visited[\spl_object_id($node)]);
    }

    private function markVisited(NodeInterface $node): void
    {
        $this->visited[\spl_object_id($node)] = true;
    }

    private function validate(NodeInterface $node, ValidationContext $context): void
    {
        foreach ($this->validators as $validator) {
            foreach ($validator->validate($node, $context) as $violation) {
                $this->violations[] = $violation;
            }
        }
    }

    /**
     * Returns the nodes in the properties of the node and in the lists the
     * properties hold, in the order of the properties.
     *
     * @return list<NodeInterface>
     */
    private function getChildren(NodeInterface $node): array
    {
        $children = [];

        foreach (\get_object_vars($node) as $value) {
            if ($value instanceof NodeInterface) {
                $children[] = $value;
            } elseif (\is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof NodeInterface) {
                        $children[] = $item;
                    }
                }
            }
        }

        return $children;
    }
}
