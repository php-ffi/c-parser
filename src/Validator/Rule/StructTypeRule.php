<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\ArrayType\IncompleteArrayType;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Node\Type\RecordType\StructType;
use FFI\Parser\Validator\Rule\Extension\GnuFlexibleArrayMemberRule;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a member of an incomplete array type (a flexible array
 * member) is only the last member of a structure:
 * `struct S { int n; int a[]; int m; };` is not valid. A flexible array
 * member of a union and the only member of a structure are checked by
 * {@see GnuFlexibleArrayMemberRule}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 *
 * @template-implements ValidatorRuleInterface<StructType>
 */
final class StructTypeRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [StructType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $members = $this->getMemberDeclarations($node);
        $last = \count($members) - 1;

        foreach ($members as $index => $member) {
            if ($index !== $last && $this->isFlexibleArray($member)) {
                yield new Violation('Flexible array member is allowed only as the last member', $member->offset);
            }
        }
    }

    /**
     * Whether the member has an incomplete array type, with the attributes
     * of its declarator or without.
     */
    private function isFlexibleArray(MemberDeclaration $member): bool
    {
        $type = $member->type;

        while ($type instanceof AttributedType) {
            $type = $type->type;
        }

        return $type instanceof IncompleteArrayType;
    }

    /**
     * @return list<MemberDeclaration> the members without the static
     *         assertions among them, empty in case of the specifier has no
     *         body
     */
    private function getMemberDeclarations(StructType $node): array
    {
        $members = [];

        foreach ($node->members ?? [] as $member) {
            if ($member instanceof MemberDeclaration) {
                $members[] = $member;
            }
        }

        return $members;
    }
}
