<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\TypeDefDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\TypeDeclaration;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\AutoSpecifier;
use FFI\Parser\Node\Type\ArrayType;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\BlockPointerType;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\FunctionType\Parameter;
use FFI\Parser\Node\Type\PointerType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Node\Type\SpecifiedType;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a missing type specifier belongs to a declaration with an
 * inferred type, `auto x = 1;`. `static x = 1;`, `const *p;`,
 * `static f(void);` and `sizeof(const)` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.10 Type inference
 *
 * @template-implements ValidatorRuleInterface<VariableDeclaration|QualifiedType|TypeDefDeclaration|FunctionDefinition|TypeDeclaration|MemberDeclaration|Parameter|PointerType|BlockPointerType|ArrayType|SpecifiedType|AttributedType|FunctionType>
 */
final class TypeInferenceRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [
            VariableDeclaration::class,
            QualifiedType::class,
            TypeDefDeclaration::class,
            FunctionDefinition::class,
            TypeDeclaration::class,
            MemberDeclaration::class,
            Parameter::class,
            PointerType::class,
            BlockPointerType::class,
            ArrayType::class,
            SpecifiedType::class,
            AttributedType::class,
            FunctionType::class,
        ];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $isMissing = match (true) {
            $node instanceof VariableDeclaration => $node->type === null && !$this->isInferred($node),
            $node instanceof QualifiedType,
            $node instanceof AttributedType => $node->type === null && !$this->isInferredType($node, $context),
            $node instanceof TypeDefDeclaration,
            $node instanceof FunctionDefinition,
            $node instanceof TypeDeclaration,
            $node instanceof MemberDeclaration,
            $node instanceof Parameter,
            $node instanceof PointerType,
            $node instanceof BlockPointerType,
            $node instanceof ArrayType,
            $node instanceof SpecifiedType => $node->type === null,
            $node instanceof FunctionType => $node->returnType === null,
        };

        if (!$isMissing) {
            return [];
        }

        return [new Violation('Type specifier is missing', $node->offset)];
    }

    /**
     * Whether the declaration has the form of an object whose type is
     * inferred from its initializer.
     */
    private function isInferred(VariableDeclaration $declaration): bool
    {
        if ($declaration->initializer === null) {
            return false;
        }

        foreach ($declaration->specifiers ?? [] as $specifier) {
            if ($specifier instanceof AutoSpecifier) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the qualifiers or the attributes of a declarator without a
     * type are the type of a declaration whose type is inferred,
     * `const auto x = 1;`.
     */
    private function isInferredType(QualifiedType|AttributedType $node, ValidationContext $context): bool
    {
        $parent = \array_last($context->parents);

        return $parent instanceof VariableDeclaration && $parent->type === $node && $this->isInferred($parent);
    }
}
