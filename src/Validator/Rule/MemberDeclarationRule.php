<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\BuiltinType\VoidType;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Node\Type\RecordType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Validator\Rule\Extension\TaggedAnonymousMemberRule;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks a member declaration: without a declarator it declares an
 * anonymous structure or union, `struct { int a; };`, and a member has
 * neither a function type nor `void`. `struct S { int; };` and
 * `struct S { int f(void); };` are not valid. A tag or a typedef name of a
 * member without a declarator, `struct S { struct T; };`, is checked by
 * {@see TaggedAnonymousMemberRule}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 *
 * @template-implements ValidatorRuleInterface<MemberDeclaration>
 */
final class MemberDeclarationRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [MemberDeclaration::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $type = $node->type;

        while ($type instanceof QualifiedType || $type instanceof AttributedType) {
            $type = $type->type;
        }

        $message = match (true) {
            $node->name === null && $node->width === null && !$this->isRecordOrTypedefName($type)
                => 'Member declaration should declare a member or an anonymous structure or union',
            $type instanceof FunctionType => 'Member should not have a function type',
            $type instanceof VoidType => 'Member should not have the incomplete type `void`',
            default => null,
        };

        if ($message === null) {
            return [];
        }

        return [new Violation($message, $node->offset)];
    }

    /**
     * Whether the type is a structure or union specifier or a typedef name.
     */
    private function isRecordOrTypedefName(?TypeInterface $type): bool
    {
        return $type instanceof RecordType || $type instanceof NamedType;
    }
}
