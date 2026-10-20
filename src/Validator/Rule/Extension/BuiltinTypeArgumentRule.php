<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\Extension;

use FFI\Parser\Node\Expression\BuiltinCallExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that the arguments of a call are expressions:
 * `__builtin_offsetof(struct S, a)` is not valid. GCC and Clang accept type
 * names among the arguments of built-in functions.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.3 Function calls
 * @link https://gcc.gnu.org/onlinedocs/gcc/Other-Builtins.html GCC, Other Built-in Functions
 *
 * @template-implements ValidatorRuleInterface<BuiltinCallExpression>
 */
final class BuiltinTypeArgumentRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [BuiltinCallExpression::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        return [new Violation('Argument of a function call should be an expression', $node->offset)];
    }
}
