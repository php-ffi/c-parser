<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\Rule\BinaryOperatorRule;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\AlignAsExpressionOperandRule;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\AlignOfExpressionOperandRule;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\SizeOfExpressionOperandRule;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\TypeOfExpressionOperandRule;
use FFI\Parser\Transformer\Rule\ExpressionOperandRule\TypeOfUnqualExpressionOperandRule;
use FFI\Parser\Transformer\Rule\FunctionCallRule;
use FFI\Parser\Transformer\Rule\ModifiedTypedefNameParameterRule;
use FFI\Parser\Transformer\Rule\OperatorPrecedenceRule;
use FFI\Parser\Transformer\Rule\ParameterNameRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\AlignAsTypeNameOperandRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\AlignOfTypeNameOperandRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\BuiltinCallTypeNameOperandRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\GenericSelectionTypeNameOperandRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\SizeOfTypeNameOperandRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\TypeOfTypeNameOperandRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\TypeOfUnqualTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use FFI\Parser\Transformer\TransformerVisitor;

/**
 * Runs the transformers over the syntax tree.
 */
final class Transformer
{
    /**
     * @var list<TransformerRuleInterface<covariant NodeInterface>>
     */
    private readonly array $transformers;

    /**
     * @var array<non-empty-string, true>
     */
    private readonly array $typedefNames;

    /**
     * @param iterable<mixed, TransformerRuleInterface<covariant NodeInterface>> $rules
     * @param iterable<mixed, non-empty-string> $typedefNames names declared in
     *        the file scope before the declarations
     */
    public function __construct(iterable $rules = [], iterable $typedefNames = [])
    {
        $this->transformers = self::formatTransformerRules($rules);
        $this->typedefNames = self::formatTypedefNames($typedefNames);
    }

    /**
     * TODO Since PHP 8.2 should be replaced to `iterator_to_array`
     *
     * @param iterable<mixed, TransformerRuleInterface<covariant NodeInterface>> $rules
     * @return list<TransformerRuleInterface<covariant NodeInterface>>
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
     * @param iterable<mixed, non-empty-string> $names
     * @return array<non-empty-string, true>
     */
    private static function formatTypedefNames(iterable $names): array
    {
        $result = [];

        foreach ($names as $name) {
            $result[$name] = true;
        }

        return $result;
    }

    /**
     * @param iterable<mixed, TransformerRuleInterface<covariant NodeInterface>> $append
     * @return array<array-key, TransformerRuleInterface<covariant NodeInterface>>
     */
    public static function getDefaultTransformers(iterable $append = []): array
    {
        return [
            new BinaryOperatorRule(),
            new FunctionCallRule(),
            new OperatorPrecedenceRule(),
            new ParameterNameRule(),
            new ModifiedTypedefNameParameterRule(),
            new SizeOfExpressionOperandRule(),
            new TypeOfExpressionOperandRule(),
            new TypeOfUnqualExpressionOperandRule(),
            new AlignAsExpressionOperandRule(),
            new AlignOfExpressionOperandRule(),
            new SizeOfTypeNameOperandRule(),
            new TypeOfTypeNameOperandRule(),
            new TypeOfUnqualTypeNameOperandRule(),
            new AlignAsTypeNameOperandRule(),
            new AlignOfTypeNameOperandRule(),
            new BuiltinCallTypeNameOperandRule(),
            new GenericSelectionTypeNameOperandRule(),
            ...$append,
        ];
    }

    /**
     * Creates the transformer with the default rules.
     *
     * @param iterable<mixed, non-empty-string> $typedefNames names declared in
     *        the file scope before the declarations
     */
    public static function createDefault(iterable $typedefNames = []): self
    {
        return new self(self::getDefaultTransformers(), $typedefNames);
    }

    /**
     * Transforms the nodes in place.
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
            new TransformerVisitor($this->transformers, $this->typedefNames),
        ]);
    }
}
