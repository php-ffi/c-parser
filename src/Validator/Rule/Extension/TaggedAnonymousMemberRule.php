<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\Extension;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Node\Type\RecordType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a member declaration without a declarator declares a
 * structure or union without a tag: `struct S { struct T; };`,
 * `struct S { struct T { int a; }; };` and `struct S { T; };` are not
 * valid. MSVC accepts them as anonymous members of the type.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 * @link https://learn.microsoft.com/en-us/cpp/c-language/anonymous-structures MSVC, Anonymous structures
 *
 * @template-implements ValidatorRuleInterface<MemberDeclaration>
 */
final class TaggedAnonymousMemberRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [MemberDeclaration::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if ($node->name !== null || $node->width !== null) {
            return [];
        }

        $type = $node->type;

        while ($type instanceof QualifiedType || $type instanceof AttributedType) {
            $type = $type->type;
        }

        if (!$type instanceof NamedType && !($type instanceof RecordType && $type->name !== null)) {
            return [];
        }

        return [new Violation('Anonymous member should be a structure or union without a tag', $node->offset)];
    }
}
