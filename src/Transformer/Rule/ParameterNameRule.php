<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\FunctionType\Parameter;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Turns a parameter of a function type, whose only parameter is a name
 * other than a typedef name, into the parameter of that name: in
 * `void f(int (x));` the parameter `x` is an `int`.
 *
 * @template-implements TransformerRuleInterface<Parameter>
 */
final class ParameterNameRule implements TransformerRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [Parameter::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if ($node->name !== null || !$node->type instanceof FunctionType) {
            return null;
        }

        $name = $this->findName($node->type, $context);

        if ($name === null) {
            return null;
        }

        return new Parameter($node->type->returnType, $name, $node->attributes, $node->specifiers, $node->offset);
    }

    /**
     * @return Identifier|null the name the only parameter of the function
     *         consists of, {@see null} in case of the parameter is other
     *         than a lone name or the name is a typedef name
     */
    private function findName(FunctionType $function, TransformationContext $context): ?Identifier
    {
        $parameter = $function->parameters->first();

        if ($parameter === null
            || \count($function->parameters) !== 1
            || $function->isVariadic
            || $function->attributes !== null
            || !$parameter->type instanceof NamedType
            || $parameter->name !== null
            || $parameter->attributes !== null
            || $parameter->specifiers->items !== []
            || $context->isTypedefName($parameter->type->name->value)) {
            return null;
        }

        return $parameter->type->name;
    }
}
