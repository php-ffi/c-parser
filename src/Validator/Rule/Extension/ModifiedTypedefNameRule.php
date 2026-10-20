<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\Extension;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\ModifiedType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a typedef name is the only type specifier: `unsigned T` is not
 * valid. GCC, Clang and MSVC accept `unsigned __int64`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 *
 * @template-implements ValidatorRuleInterface<ModifiedType>
 */
final class ModifiedTypedefNameRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [ModifiedType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if (!$node->type instanceof NamedType) {
            return [];
        }

        return [
            new Violation(
                \sprintf('Typedef name `%s` should not be combined with other type specifiers', $node->type->name->value),
                $node->offset,
            ),
        ];
    }
}
