<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\ArrayType;
use FFI\Parser\Node\Type\AtomicType;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that the type name of an atomic type specifier is not an array, a
 * function, an atomic or a qualified type: `_Atomic(int[3])` and
 * `_Atomic(const int)` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.5 Atomic type specifiers
 *
 * @template-implements ValidatorRuleInterface<AtomicType>
 */
final class AtomicTypeRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [AtomicType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $type = $node->type;

        while ($type instanceof AttributedType) {
            $type = $type->type;
        }

        $kind = match (true) {
            $type instanceof ArrayType => 'an array',
            $type instanceof FunctionType => 'a function',
            $type instanceof AtomicType => 'an atomic',
            $type instanceof QualifiedType => 'a qualified',
            default => null,
        };

        if ($kind === null) {
            return [];
        }

        return [
            new Violation(\sprintf('Atomic type specifier should not be applied to %s type', $kind), $node->offset),
        ];
    }
}
