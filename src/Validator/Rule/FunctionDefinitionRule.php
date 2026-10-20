<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\BuiltinType\VoidType;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a function definition declares a function and has no named
 * `void` parameter: `int x {}`, `F f {}` and `int f(void x) {}` are not
 * valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.9.1 Function definitions
 *
 * @template-implements ValidatorRuleInterface<FunctionDefinition>
 */
final class FunctionDefinitionRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [FunctionDefinition::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if (!$node->type instanceof FunctionType) {
            return [new Violation('Declarator of a function definition should declare a function', $node->offset)];
        }

        $parameters = $node->type->parameters->items;

        if (\count($parameters) === 1 && $parameters[0]->name !== null && $this->isVoid($parameters[0]->type)) {
            return [new Violation('Parameter of type `void` should not have a name', $parameters[0]->offset)];
        }

        return [];
    }

    private function isVoid(?TypeInterface $type): bool
    {
        while ($type instanceof AttributedType) {
            $type = $type->type;
        }

        return $type instanceof VoidType;
    }
}
