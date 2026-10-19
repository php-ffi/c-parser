<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Traverser\Action;
use FFI\Parser\Traverser\Context;
use FFI\Parser\Traverser\VisitorInterface;

/**
 * A single run of the validators over the syntax tree, which collects the
 * violations they find.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 */
final class ValidatorVisitor implements VisitorInterface
{
    /**
     * Violations in the order they are found
     *
     * @var list<Violation>
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public array $violations = [];

    /**
     * @var array<class-string<NodeInterface>, list<ValidatorRuleInterface<covariant NodeInterface>>>
     */
    private array $validatorsByClass = [];

    public function __construct(
        /**
         * @var non-empty-list<ValidatorRuleInterface<covariant NodeInterface>>
         */
        private readonly array $validators,
    ) {}

    public function enter(NodeInterface $node, Context $context): ?Action
    {
        $validators = $this->getValidators($node);

        if ($validators === []) {
            return null;
        }

        $context = new ValidationContext($context->parents, $context->declarations);

        foreach ($validators as $validator) {
            // @phpstan-ignore-next-line the node is an instance of one of the classes the validator lists
            foreach ($validator->validate($node, $context) as $violation) {
                $this->violations[] = $violation;
            }
        }

        return null;
    }

    /**
     * @return list<ValidatorRuleInterface<covariant NodeInterface>>
     */
    private function getValidators(NodeInterface $node): array
    {
        return $this->validatorsByClass[$node::class] ??= $this->findValidators($node);
    }

    /**
     * @return list<ValidatorRuleInterface<covariant NodeInterface>>
     */
    private function findValidators(NodeInterface $node): array
    {
        $validators = [];

        foreach ($this->validators as $validator) {
            foreach ($validator->getNodeClasses() as $class) {
                if ($node instanceof $class) {
                    $validators[] = $validator;

                    break;
                }
            }
        }

        return $validators;
    }

    public function leave(NodeInterface $node, Context $context): ?Action
    {
        return null;
    }
}
