<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\Extension;

use FFI\Parser\Node\Expression\GenericSelectionExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that the controlling operand of a generic selection is an
 * expression: `_Generic(int, int: 1)` is not valid. GCC, Clang and C2y
 * accept a type name there.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.2.1 Generic selection
 *
 * @template-implements ValidatorRuleInterface<GenericSelectionExpression>
 */
final class GenericTypeOperandRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [GenericSelectionExpression::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if (!$node->operand instanceof TypeInterface) {
            return [];
        }

        return [
            new Violation('Controlling operand of a generic selection should be an expression', $node->operand->offset),
        ];
    }
}
