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
 */
final class TransformerVisitor implements VisitorInterface
{
    private readonly Scope $scope;

    /**
     * @var array<class-string<NodeInterface>, array<int<0, max>, TransformerRuleInterface<covariant NodeInterface>>>
     */
    private array $transformersByClass = [];

    public function __construct(
        /**
         * @var non-empty-list<TransformerRuleInterface<covariant NodeInterface>>
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
        $result = $node;

        if ($this->getTransformers($node) !== []) {
            $transformationContext = new TransformationContext(
                parents: $context->parents,
                declarations: $context->declarations,
                scope: $this->scope,
            );

            $result = $this->transform($node, $transformationContext);
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

    private function transform(NodeInterface $node, TransformationContext $context): NodeInterface
    {
        $position = -1;
        $isReplaced = true;

        while ($isReplaced) {
            $isReplaced = false;

            foreach ($this->getTransformers($node) as $index => $transformer) {
                if ($index <= $position) {
                    continue;
                }

                $position = $index;
                $class = $node::class;
                // @phpstan-ignore-next-line the node is an instance of one of the classes the transformer lists
                $node = $transformer->transform($node, $context) ?? $node;

                if ($node::class !== $class) {
                    $isReplaced = true;

                    break;
                }
            }
        }

        return $node;
    }

    /**
     * @return array<int<0, max>, TransformerRuleInterface<covariant NodeInterface>>
     */
    private function getTransformers(NodeInterface $node): array
    {
        return $this->transformersByClass[$node::class] ??= $this->findTransformers($node);
    }

    /**
     * @return array<int<0, max>, TransformerRuleInterface<covariant NodeInterface>>
     */
    private function findTransformers(NodeInterface $node): array
    {
        $transformers = [];

        foreach ($this->transformers as $index => $transformer) {
            foreach ($transformer->getNodeClasses() as $class) {
                if ($node instanceof $class) {
                    $transformers[$index] = $transformer;

                    break;
                }
            }
        }

        return $transformers;
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
