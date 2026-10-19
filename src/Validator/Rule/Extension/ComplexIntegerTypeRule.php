<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\Extension;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\BuiltinType\CharType;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Node\Type\ModifiedType;
use FFI\Parser\Node\Type\ModifiedType\TypeModifier;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that `_Complex` is applied to a floating type: `_Complex int`,
 * `unsigned _Complex char` and `_Complex` without a type specifier are not
 * valid. GCC and Clang accept them as complex integer types.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 * @link https://gcc.gnu.org/onlinedocs/gcc/Complex.html GCC, Complex Numbers
 *
 * @template-implements ValidatorRuleInterface<ModifiedType>
 */
final class ComplexIntegerTypeRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [ModifiedType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if (!\in_array(TypeModifier::Complex, $node->modifiers, true)) {
            return [];
        }

        if ($node->type !== null && !$node->type instanceof IntType && !$node->type instanceof CharType) {
            return [];
        }

        return [new Violation('Type specifier `_Complex` should be applied to a floating type', $node->offset)];
    }
}
