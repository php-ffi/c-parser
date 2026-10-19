<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\AtomicType;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\BuiltinType\BitIntType;
use FFI\Parser\Node\Type\BuiltinType\BoolType;
use FFI\Parser\Node\Type\BuiltinType\CharType;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Node\Type\EnumType;
use FFI\Parser\Node\Type\ModifiedType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Node\Type\SpecifiedType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Node\Type\TypeOfType;
use FFI\Parser\Node\Type\TypeOfTypeType;
use FFI\Parser\Node\Type\TypeOfUnqualType;
use FFI\Parser\Node\Type\TypeOfUnqualTypeType;
use FFI\Parser\Validator\ValidatorRuleInterface;

/**
 * Base of the rules that require an integer type where the tree shows the
 * type: a type behind a typedef name or `typeof` is accepted.
 *
 * @template TNode of NodeInterface
 *
 * @template-implements ValidatorRuleInterface<TNode>
 */
abstract class IntegerTypeRule implements ValidatorRuleInterface
{
    /**
     * Returns the base of the type specifiers: the type without the
     * qualifiers, the other specifiers, `_Atomic(...)`, the modifiers such
     * as `unsigned` and the attributes of a declarator, {@see null} in case
     * of no base is written.
     */
    protected static function findBaseType(?TypeInterface $type): ?TypeInterface
    {
        while ($type instanceof QualifiedType
            || $type instanceof SpecifiedType
            || $type instanceof AtomicType
            || $type instanceof ModifiedType
            || $type instanceof AttributedType
        ) {
            $type = $type->type;
        }

        return $type;
    }

    /**
     * Whether the base of the type specifiers may be an integer type: a
     * standard one (`unsigned` alone gives `int`), `bool`, an enumeration,
     * a bit-precise one or a type the tree does not show.
     */
    protected static function isIntegerBase(?TypeInterface $base): bool
    {
        return $base === null
            || $base instanceof IntType
            || $base instanceof CharType
            || $base instanceof BoolType
            || $base instanceof BitIntType
            || $base instanceof EnumType
            || $base instanceof NamedType
            || $base instanceof TypeOfType
            || $base instanceof TypeOfTypeType
            || $base instanceof TypeOfUnqualType
            || $base instanceof TypeOfUnqualTypeType;
    }
}
