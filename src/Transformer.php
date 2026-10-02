<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Transformer\TransformerRuleInterface;
use FFI\Parser\Transformer\TransformerVisitor;

/**
 * Runs the transformers over the syntax tree.
 *
 * Each transformer is given every node once, even a node shared by several
 * declarations, after the children of the node, in the order the
 * transformers are listed. Each one is given the node the previous one
 * returned.
 */
final class Transformer
{
    /**
     * @var list<TransformerRuleInterface>
     */
    private readonly array $transformers;

    /**
     * @param iterable<mixed, TransformerRuleInterface> $rules
     */
    public function __construct(iterable $rules = [])
    {
        $this->transformers = self::formatTransformerRules($rules);
    }

    /**
     * TODO Since PHP 8.2 should be replaced to `iterator_to_array`
     *
     * @param iterable<mixed, TransformerRuleInterface> $rules
     * @return list<TransformerRuleInterface>
     */
    private static function formatTransformerRules(iterable $rules): array
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
     * Transforms the declarations, the ones {@see Parser::parse()} returns
     * or any other list of nodes. The nodes are changed in place.
     *
     * @param list<RootDeclarationInterface> $declarations
     * @return list<RootDeclarationInterface> the declarations with the
     *         replaced ones in their places
     * @throws TraverserException in case of a transformer breaks the syntax
     *         tree
     */
    public function transform(array $declarations): array
    {
        if ($this->transformers === []) {
            return $declarations;
        }

        return Traverser::execute($declarations, [
            new TransformerVisitor($this->transformers),
        ]);
    }
}
