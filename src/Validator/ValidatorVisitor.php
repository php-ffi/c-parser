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
     * @var list<Violation>
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public array $violations = [];

    public function __construct(
        /**
         * @var non-empty-list<ValidatorRuleInterface>
         */
        private readonly array $validators,
    ) {}

    /**
     * An alias accessor method for {@see self::$violations} property.
     *
     * @api
     *
     * @return list<Violation> violations in the order they are found
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    public function enter(NodeInterface $node, Context $context): ?Action
    {
        $context = new ValidationContext($context->parents, $context->declarations);

        foreach ($this->validators as $validator) {
            foreach ($validator->validate($node, $context) as $violation) {
                $this->violations[] = $violation;
            }
        }

        return null;
    }

    public function leave(NodeInterface $node, Context $context): ?Action
    {
        return null;
    }
}
