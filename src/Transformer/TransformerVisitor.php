<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\TypeDefDeclaration;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\EnumType\Enumerator;
use FFI\Parser\Node\Type\FunctionType\Parameter;
use FFI\Parser\Node\Type\FunctionType\ParameterList;
use FFI\Parser\Transformer\TransformationContext\Scope;
use FFI\Parser\Traverser\Action;
use FFI\Parser\Traverser\Context;
use FFI\Parser\Traverser\VisitorInterface;

/**
 * A single run of the transformers over the syntax tree, which gives each
 * node to them after its children and declares the names of the
 * transformed declarations.
 *
 * A name is declared when its whole declaration is left, a parameter list
 * opens a function prototype scope.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 *
 * @readonly
 */
final class TransformerVisitor implements VisitorInterface
{
    private readonly Scope $scope;

    public function __construct(
        /**
         * @var non-empty-list<TransformerRuleInterface>
         */
        private readonly array $transformers,
    ) {
        $this->scope = new Scope();
    }

    public function enter(NodeInterface $node, Context $context): ?Action
    {
        if ($node instanceof ParameterList) {
            $this->scope->enter();
        }

        return null;
    }

    public function leave(NodeInterface $node, Context $context): ?NodeInterface
    {
        $context = new TransformationContext($context->parents, $context->declarations, $this->scope);
        $result = $node;

        foreach ($this->transformers as $transformer) {
            $result = $transformer->transform($result, $context) ?? $result;
        }

        $this->declare($result);

        if ($node instanceof ParameterList) {
            $this->scope->leave();
        }

        if ($result === $node) {
            return null;
        }

        return $result;
    }

    private function declare(NodeInterface $node): void
    {
        if ($node instanceof TypeDefDeclaration) {
            $this->scope->declareTypedefName($node->name->value);
        } elseif ($node instanceof DeclaratorDeclaration || $node instanceof Enumerator) {
            $this->scope->declareNonTypeName($node->name->value);
        } elseif ($node instanceof Parameter && $node->name !== null) {
            $this->scope->declareNonTypeName($node->name->value);
        }
    }
}
