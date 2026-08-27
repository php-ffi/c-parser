<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Validator\TreeWalker;
use FFI\Parser\Validator\ValidatorInterface;
use FFI\Parser\Validator\Violation;

/**
 * Runs the validators over the syntax tree.
 *
 * The tree is walked once, every node is visited once, even a node shared
 * by several declarations. Each validator is given each node, in the order
 * the validators are listed.
 */
final class Validator
{
    /**
     * @var list<ValidatorInterface>
     */
    private readonly array $validators;

    /**
     * @param iterable<mixed, ValidatorInterface> $validators
     */
    public function __construct(iterable $validators = [])
    {
        $this->validators = \is_array($validators)
            ? \array_values($validators)
            : \iterator_to_array($validators, false);
    }

    /**
     * TODO Add default rules
     */
    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * Validates the declarations, the ones {@see Parser::parse()} returns or
     * any other list of nodes.
     *
     * @param list<RootDeclarationInterface> $declarations
     * @return list<Violation> violations in the order of their offsets, empty
     *         in case of the declarations break no rule
     */
    public function validate(array $declarations): array
    {
        if ($this->validators === []) {
            return [];
        }

        $violations = (new TreeWalker($this->validators, $declarations))->walk();

        \usort($violations, static fn(Violation $a, Violation $b): int => $a->offset <=> $b->offset);

        return $violations;
    }
}
