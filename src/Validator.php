<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Validator\Rule\ArrayDeclaratorRule;
use FFI\Parser\Validator\Rule\AtomicTypeRule;
use FFI\Parser\Validator\Rule\Extension\AlignOfExpressionRule;
use FFI\Parser\Validator\Rule\Extension\BuiltinTypeArgumentRule;
use FFI\Parser\Validator\Rule\Extension\ComplexIntegerTypeRule;
use FFI\Parser\Validator\Rule\Extension\GnuFlexibleArrayMemberRule;
use FFI\Parser\Validator\Rule\Extension\ModifiedTypedefNameRule;
use FFI\Parser\Validator\Rule\Extension\TaggedAnonymousMemberRule;
use FFI\Parser\Validator\Rule\FunctionDeclaratorRule;
use FFI\Parser\Validator\Rule\FunctionDefinitionRule;
use FFI\Parser\Validator\Rule\InitializerRule;
use FFI\Parser\Validator\Rule\IntegerTypeRule\BitFieldRule;
use FFI\Parser\Validator\Rule\IntegerTypeRule\EnumTypeRule;
use FFI\Parser\Validator\Rule\MemberDeclarationRule;
use FFI\Parser\Validator\Rule\StorageClassSpecifierRule;
use FFI\Parser\Validator\Rule\StructTypeRule;
use FFI\Parser\Validator\Rule\TypeInferenceRule;
use FFI\Parser\Validator\Rule\TypeSpecifierRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\ValidatorVisitor;
use FFI\Parser\Validator\Violation;

/**
 * Runs the validators over the syntax tree.
 */
final class Validator
{
    /**
     * @var list<ValidatorRuleInterface<covariant NodeInterface>>
     */
    private readonly array $validators;

    /**
     * @param iterable<mixed, ValidatorRuleInterface<covariant NodeInterface>> $rules
     */
    public function __construct(iterable $rules = [])
    {
        $this->validators = self::formatValidatorRules($rules);
    }

    /**
     * TODO Since PHP 8.2 should be replaced to `iterator_to_array`
     *
     * @param iterable<mixed, ValidatorRuleInterface<covariant NodeInterface>> $rules
     * @return list<ValidatorRuleInterface<covariant NodeInterface>>
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
     * Returns the default rules followed by the given ones.
     *
     * @param iterable<mixed, ValidatorRuleInterface<covariant NodeInterface>> $append
     * @return array<array-key, ValidatorRuleInterface<covariant NodeInterface>>
     */
    public static function getDefaultRules(iterable $append = []): array
    {
        return [
            new TypeSpecifierRule(),
            new ComplexIntegerTypeRule(),
            new ModifiedTypedefNameRule(),
            new TypeInferenceRule(),
            new StorageClassSpecifierRule(),
            new AtomicTypeRule(),
            new StructTypeRule(),
            new GnuFlexibleArrayMemberRule(),
            new MemberDeclarationRule(),
            new TaggedAnonymousMemberRule(),
            new BitFieldRule(),
            new EnumTypeRule(),
            new ArrayDeclaratorRule(),
            new FunctionDeclaratorRule(),
            new FunctionDefinitionRule(),
            new InitializerRule(),
            new AlignOfExpressionRule(),
            new BuiltinTypeArgumentRule(),
            ...$append,
        ];
    }

    public static function createDefault(): self
    {
        return new self(self::getDefaultRules());
    }

    /**
     * @param list<RootDeclarationInterface> $declarations
     * @return list<Violation> violations in the order of their offsets
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
     */
    public function validate(array $declarations): array
    {
        if ($this->validators === []) {
            return [];
        }

        Traverser::execute($declarations, [
            $visitor = new ValidatorVisitor($this->validators),
        ]);

        $violations = $visitor->violations;

        \usort($violations, Violation::sort(...));

        return $violations;
    }
}
