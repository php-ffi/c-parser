<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Validator\Rule\Extension\AlignOfExpressionRule;
use FFI\Parser\Validator\Rule\Extension\BuiltinTypeArgumentRule;
use FFI\Parser\Validator\Rule\Extension\ComplexIntegerTypeRule;
use FFI\Parser\Validator\Rule\Extension\GnuFlexibleArrayMemberRule;
use FFI\Parser\Validator\Rule\Extension\ModifiedTypedefNameRule;
use FFI\Parser\Validator\Rule\Extension\TaggedAnonymousMemberRule;
use FFI\Parser\Validator\ValidatorRuleInterface;

/**
 * Extension of C23 a compiler accepts: a construct of its own syntax or a
 * constraint of C23 it does not check.
 */
enum Feature
{
    /**
     * GCC attribute specifiers, `__attribute__((aligned(8)))`
     */
    case GnuAttributes;

    /**
     * Name of the symbol of a declaration in the object file,
     * `int f(void) __asm__("_f");`
     */
    case AsmLabels;

    /**
     * GCC spellings of the keywords of C23 with two leading underscores,
     * `__const__`, `__restrict`, `__inline`, `__thread`
     */
    case GnuKeywordSpellings;

    /**
     * GCC `__extension__` before a declaration, a member or an expression
     */
    case ExtensionKeyword;

    /**
     * MSVC attribute specifiers, `__declspec(dllimport)`
     */
    case DeclSpec;

    /**
     * MSVC calling conventions, `int __stdcall f(void);`
     */
    case CallingConventions;

    /**
     * PHP FFI calling conventions after a declarator and after `struct`,
     * `union` or `enum`, `int f(void) __cdecl;`. The conventions themselves
     * require {@see Feature::CallingConventions}
     */
    case TrailingCallingConventions;

    /**
     * MSVC modifiers of pointers, `int * __ptr64 p;`
     */
    case PointerModifiers;

    /**
     * MSVC `__forceinline` function specifier
     */
    case ForceInline;

    /**
     * MSVC suffixes of the sized integer types, `1i64`, `1ui64`
     */
    case MsIntegerSuffixes;

    /**
     * PHP FFI last member declaration without its semicolon,
     * `struct S { int a };`
     */
    case OptionalLastMemberSemicolon;

    /**
     * PHP FFI keywords of C23 and of the extensions as names, `int bool;`,
     * `enum { false, true };`
     */
    case KeywordIdentifiers;

    /**
     * GCC structures and unions without members, `struct S {};`
     */
    case EmptyRecords;

    /**
     * GCC semicolons alone at the file scope and among the members,
     * `int a;;`
     */
    case ExtraSemicolons;

    /**
     * GCC `alignof` applied to an expression, `__alignof__ x`
     */
    case AlignOfExpressions;

    /**
     * GCC and Clang type names among the arguments of the built-in
     * functions, `__builtin_offsetof(struct S, a)`
     */
    case BuiltinTypeArguments;

    /**
     * GCC statement expressions, `({ int y = x; y * 2; })`
     */
    case StatementExpressions;

    /**
     * Complex integer types, `_Complex int`, and `_Complex` without a type
     * specifier
     */
    case ComplexIntegers;

    /**
     * Typedef name with a type modifier, `unsigned __int64`
     */
    case ModifiedTypedefNames;

    /**
     * Flexible array member in a union and as the only member of a
     * structure, `union U { int a[]; };`
     */
    case GnuFlexibleArrayMembers;

    /**
     * Member declaration without a declarator, whose type is a structure
     * or union with a tag or a typedef name, `struct S { struct T; };`
     */
    case TaggedAnonymousMembers;

    /**
     * GCC imaginary constants, `2.0i`, `3j`
     */
    case ImaginaryConstants;

    /**
     * GCC suffix of a `__float128` constant, `1.0q`
     */
    case Float128Suffix;

    /**
     * GCC suffix of a `__float80` constant, `1.0w`
     */
    case Float80Suffix;

    /**
     * GCC escape sequence of the escape character, `"\e"`
     */
    case EscapeE;

    /**
     * GCC designator of a range of elements, `[0 ... 3] = 1`
     */
    case RangeDesignators;

    /**
     * GCC conditional operator without its middle operand, `a ?: b`
     */
    case OmittedConditionalOperand;

    /**
     * GCC real and imaginary parts of a complex number, `__real__ z`,
     * `__imag__ z`
     */
    case ComplexParts;

    /**
     * GCC basic assembler statement at the file scope, `__asm__("nop");`
     */
    case AsmDeclarations;

    /**
     * Clang with the Microsoft extensions: enumeration without
     * enumerators, `enum E {};`
     */
    case EmptyEnums;

    /**
     * Clang block pointers, `void (^callback)(int);`
     */
    case BlockPointers;

    /**
     * Clang nullability of a pointer, `int * _Nonnull p;`
     */
    case Nullability;

    /**
     * Clang signing of a pointer, `void * __ptrauth(1, 0, 42) p;`
     */
    case PointerAuth;

    /**
     * MSVC pointer with a base, `void __based(base) *p;`
     */
    case BasedPointers;

    /**
     * Lists the rules that reject the extension, the ones the feature
     * removes. Empty in case of the extension is a syntax error without the
     * feature.
     *
     * @return list<class-string<ValidatorRuleInterface<covariant NodeInterface>>>
     */
    public function getRuleClasses(): array
    {
        return match ($this) {
            self::ComplexIntegers => [ComplexIntegerTypeRule::class],
            self::ModifiedTypedefNames => [ModifiedTypedefNameRule::class],
            self::GnuFlexibleArrayMembers => [GnuFlexibleArrayMemberRule::class],
            self::TaggedAnonymousMembers => [TaggedAnonymousMemberRule::class],
            self::AlignOfExpressions => [AlignOfExpressionRule::class],
            self::BuiltinTypeArguments => [BuiltinTypeArgumentRule::class],
            self::GnuAttributes,
            self::AsmLabels,
            self::GnuKeywordSpellings,
            self::ExtensionKeyword,
            self::DeclSpec,
            self::CallingConventions,
            self::TrailingCallingConventions,
            self::PointerModifiers,
            self::ForceInline,
            self::MsIntegerSuffixes,
            self::OptionalLastMemberSemicolon,
            self::KeywordIdentifiers,
            self::EmptyRecords,
            self::ExtraSemicolons,
            self::StatementExpressions,
            self::ImaginaryConstants,
            self::Float128Suffix,
            self::Float80Suffix,
            self::EscapeE,
            self::RangeDesignators,
            self::OmittedConditionalOperand,
            self::ComplexParts,
            self::AsmDeclarations,
            self::EmptyEnums,
            self::BlockPointers,
            self::Nullability,
            self::PointerAuth,
            self::BasedPointers => [],
        };
    }
}
