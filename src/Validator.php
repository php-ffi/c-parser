<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Validator\TreeWalker;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Runs the validators over the syntax tree.
 *
 * Each validator is given every node once, even a node shared by several
 * declarations, in the order the validators are listed.
 */
final class Validator
{
    /**
     * @var list<ValidatorRuleInterface>
     */
    private readonly array $validators;

    /**
     * @param iterable<mixed, ValidatorRuleInterface> $rules
     */
    public function __construct(iterable $rules = [])
    {
        $this->validators = self::formatValidatorRules($rules);
    }

    /**
     * TODO Since PHP 8.2 should be replaced to `iterator_to_array`
     *
     * @param iterable<mixed, ValidatorRuleInterface> $rules
     * @return list<ValidatorRuleInterface>
     */
    private static function formatValidatorRules(iterable $rules): array
    {
        return match (true) {
            $rules instanceof \Traversable => \iterator_to_array($rules, false),
            \array_is_list($rules) => $rules,
            default => \array_values($rules),
        };
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

        $violations = (new TreeWalker($this->validators, $declarations))
            ->walk();

        \usort($violations, static fn(Violation $a, Violation $b): int => $a->offset <=> $b->offset);

        return $violations;
    }
}
