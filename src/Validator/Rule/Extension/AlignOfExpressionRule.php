<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\Extension;

use FFI\Parser\Node\Expression\AlignOfExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that the operand of `alignof` is a type name: `alignof x` is not
 * valid. GCC and Clang accept an expression.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.4.4 The sizeof and alignof operators
 * @link https://gcc.gnu.org/onlinedocs/gcc/Alignment.html GCC, Alignment
 *
 * @template-implements ValidatorRuleInterface<AlignOfExpression>
 */
final class AlignOfExpressionRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [AlignOfExpression::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        return [new Violation('Operand of alignof should be a type name', $node->offset)];
    }
}
