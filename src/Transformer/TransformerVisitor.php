<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Traverser\Action;
use FFI\Parser\Traverser\Context;
use FFI\Parser\Traverser\VisitorInterface;

/**
 * A single run of the transformers over the syntax tree, which gives each
 * node to them after its children.
 *
 * @readonly
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 */
final class TransformerVisitor implements VisitorInterface
{
    public function __construct(
        /**
         * @var non-empty-list<TransformerRuleInterface>
         */
        private readonly array $transformers,
    ) {}

    public function enter(NodeInterface $node, Context $context): ?Action
    {
        return null;
    }

    public function leave(NodeInterface $node, Context $context): ?NodeInterface
    {
        $context = new TransformationContext($context->parents, $context->declarations);
        $result = $node;

        foreach ($this->transformers as $transformer) {
            $result = $transformer->transform($result, $context) ?? $result;
        }

        return $result === $node ? null : $result;
    }
}
