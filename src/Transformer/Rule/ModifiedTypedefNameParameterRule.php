<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\FunctionType\Parameter;
use FFI\Parser\Node\Type\ModifiedType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Turns a parameter of modifiers alone, named by a typedef name, into an
 * unnamed parameter of the modified typedef name: in
 * `void f(unsigned __int64);` the parameter is an `unsigned __int64`.
 *
 * @template-implements TransformerRuleInterface<Parameter>
 */
final class ModifiedTypedefNameParameterRule implements TransformerRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [Parameter::class];
    }

    public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
    {
        if ($node->name === null || !$context->isTypedefName($node->name->value)) {
            return null;
        }

        $type = $this->findType($node->type, new NamedType($node->name, $node->name->offset));

        if ($type === null) {
            return null;
        }

        return new Parameter($type, null, $node->attributes, $node->specifiers, $node->offset);
    }

    /**
     * @return TypeInterface|null the type with the typedef name as the base of
     *         its modifiers, {@see null} for other types
     */
    private function findType(?TypeInterface $type, NamedType $name): ?TypeInterface
    {
        if ($type instanceof QualifiedType) {
            $modified = $this->findType($type->type, $name);

            if ($modified === null) {
                return null;
            }

            return new QualifiedType($type->qualifiers, $modified, $type->offset);
        }

        if (!$type instanceof ModifiedType || $type->type !== null) {
            return null;
        }

        return new ModifiedType($type->modifiers, $name, $type->offset);
    }
}
