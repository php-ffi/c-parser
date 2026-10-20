<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\Extension;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\ArrayType\IncompleteArrayType;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\RecordType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Node\Type\RecordType\UnionType;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a flexible array member belongs to a structure with another
 * named member: `union U { int a[]; };` and `struct S { int a[]; };` are not
 * valid. GCC and Clang accept them.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 * @link https://gcc.gnu.org/onlinedocs/gcc/Flexible-Array-Members.html GCC, Flexible Array Members
 *
 * @template-implements ValidatorRuleInterface<RecordType>
 */
final class GnuFlexibleArrayMemberRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [RecordType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $members = $this->getNamedMembers($node);

        foreach ($members as $member) {
            if (!$this->isFlexibleArray($member)) {
                continue;
            }

            if ($node instanceof UnionType) {
                yield new Violation('Flexible array member should not be a member of a union', $member->offset);
            } elseif (\count($members) === 1) {
                yield new Violation('Flexible array member should not be the only member', $member->offset);
            }
        }
    }

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
     *         assertions and the unnamed bit-fields, empty without a body
     */
    private function getNamedMembers(RecordType $node): array
    {
        $members = [];

        foreach ($node->members ?? [] as $member) {
            if ($member instanceof MemberDeclaration && ($member->name !== null || $member->width === null)) {
                $members[] = $member;
            }
        }

        return $members;
    }
}
