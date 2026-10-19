<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Preset\ClangTarget;
use FFI\Parser\Preset\Feature;
use FFI\Parser\Validator\ValidatorRuleInterface;

/**
 * What a compiler knows before the header: the extensions of C23 it
 * accepts and the typedef names it declares itself.
 */
final class Preset
{
    /**
     * @var list<Feature>
     */
    private const GCC_FEATURES = [
        Feature::GnuAttributes,
        Feature::AsmLabels,
        Feature::ExtensionKeyword,
        Feature::GnuKeywordSpellings,
        Feature::AlignOfExpressions,
        Feature::BuiltinTypeArguments,
        Feature::StatementExpressions,
        Feature::EmptyRecords,
        Feature::ExtraSemicolons,
        Feature::ComplexIntegers,
        Feature::ModifiedTypedefNames,
        Feature::GnuFlexibleArrayMembers,
        Feature::ImaginaryConstants,
        Feature::Float128Suffix,
        Feature::EscapeE,
        Feature::RangeDesignators,
        Feature::OmittedConditionalOperand,
        Feature::ComplexParts,
        Feature::AsmDeclarations,
    ];

    /**
     * @var list<Feature>
     */
    private const CLANG_FEATURES = [
        Feature::CallingConventions,
        Feature::BlockPointers,
        Feature::Nullability,
        Feature::PointerAuth,
    ];

    /**
     * @var list<Feature>
     */
    private const CLANG_MS_FEATURES = [
        Feature::TaggedAnonymousMembers,
        Feature::DeclSpec,
        Feature::PointerModifiers,
        Feature::ForceInline,
        Feature::MsIntegerSuffixes,
        Feature::EmptyEnums,
    ];

    /**
     * @var list<Feature>
     */
    private const MSVC_FEATURES = [
        Feature::GnuKeywordSpellings,
        Feature::ExtraSemicolons,
        Feature::ModifiedTypedefNames,
        Feature::GnuFlexibleArrayMembers,
        Feature::TaggedAnonymousMembers,
        Feature::DeclSpec,
        Feature::CallingConventions,
        Feature::PointerModifiers,
        Feature::ForceInline,
        Feature::MsIntegerSuffixes,
        Feature::BasedPointers,
    ];

    /**
     * @var list<Feature>
     */
    private const PHP_FFI_FEATURES = [
        Feature::GnuAttributes,
        Feature::AsmLabels,
        Feature::ExtensionKeyword,
        Feature::GnuKeywordSpellings,
        Feature::AlignOfExpressions,
        Feature::EmptyRecords,
        Feature::DeclSpec,
        Feature::CallingConventions,
        Feature::OptionalLastMemberSemicolon,
        Feature::TrailingCallingConventions,
        Feature::KeywordIdentifiers,
        Feature::EscapeE,
    ];

    /**
     * @var list<non-empty-string>
     */
    private const GCC_TYPEDEF_NAMES = [
        '__builtin_va_list',
        '__builtin_ms_va_list',
        '__builtin_sysv_va_list',
        '__int128',
        '__int128__',
        '__int128_t',
        '__uint128_t',
        '__float80',
        '__float128',
        '__ibm128',
        '__ieee128',
        '__fp16',
        '__bf16',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const CLANG_TYPEDEF_NAMES = [
        '__builtin_va_list',
        '__builtin_ms_va_list',
        '__builtin_zos_va_list',
        '__int128',
        '__int128_t',
        '__uint128_t',
        '__float128',
        '__ibm128',
        '__fp16',
        '__bf16',
        '__NSConstantString',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const MSVC_TYPEDEF_NAMES = [
        '__int8',
        '__int16',
        '__int32',
        '__int64',
        '_int8',
        '_int16',
        '_int32',
        '_int64',
        '__wchar_t',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const PHP_FFI_TYPEDEF_NAMES = [
        'int8_t',
        'uint8_t',
        'int16_t',
        'uint16_t',
        'int32_t',
        'uint32_t',
        'int64_t',
        'uint64_t',
        'intptr_t',
        'uintptr_t',
        'size_t',
        'ssize_t',
        'ptrdiff_t',
        'off_t',
        'va_list',
        '__builtin_va_list',
        '__gnuc_va_list',
    ];

    public function __construct(
        /**
         * Extensions of C23 the compiler accepts
         *
         * @var list<Feature>
         */
        public readonly array $features = [],
        /**
         * Typedef names built into the compiler: the identifiers the
         * compiler reads as typedef names without a declaration in the
         * header, such as `__builtin_va_list` or `__int64`
         *
         * @var list<non-empty-string>
         */
        public readonly array $typedefNames = [],
    ) {}

    /**
     * Creates the preset of C23 itself, which accepts no extensions and
     * knows no typedef names.
     */
    public static function c23(): self
    {
        return new self();
    }

    /**
     * Creates the preset that accepts every extension of C23 the parser
     * knows and knows the typedef names of every compiler, the default one
     * of {@see Parser}.
     */
    public static function default(): self
    {
        $typedefNames = \array_values(\array_unique([
            ...self::GCC_TYPEDEF_NAMES,
            ...self::CLANG_TYPEDEF_NAMES,
            ...ClangTarget::All->getTypedefNames(),
            ...self::MSVC_TYPEDEF_NAMES,
            ...self::PHP_FFI_TYPEDEF_NAMES,
        ]));

        return new self(Feature::cases(), $typedefNames);
    }

    /**
     * Creates the preset of GCC: the GNU extensions and the typedef names
     * common to its targets, the variable argument lists, the 128-bit
     * integers and the extended floating types.
     *
     * @link https://gcc.gnu.org/onlinedocs/gcc/C-Extensions.html GCC, Extensions to the C Language Family
     * @link https://github.com/gcc-mirror/gcc/blob/master/gcc/c-family/c-common.cc GCC, `c_common_nodes_and_builtins()`
     * @link https://gcc.gnu.org/onlinedocs/gcc/Floating-Types.html GCC, Additional Floating Types
     * @link https://gcc.gnu.org/onlinedocs/gcc/_005f_005fint128.html GCC, 128-bit Integers
     */
    public static function gcc(): self
    {
        return new self([...self::GCC_FEATURES, Feature::Float80Suffix], self::GCC_TYPEDEF_NAMES);
    }

    /**
     * Creates the preset of Clang: the GNU extensions, the calling
     * conventions, the blocks, the nullability and the signing of pointers
     * and the typedef names of Clang and of the target architecture. The Microsoft extensions (`-fms-extensions`) add the
     * extensions and the typedef names of MSVC.
     *
     * @link https://clang.llvm.org/docs/LanguageExtensions.html Clang, Language Extensions
     * @link https://github.com/llvm/llvm-project/blob/main/clang/lib/Sema/Sema.cpp Clang, `Sema::Initialize()`
     * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/TokenKinds.def Clang, the keywords (`KEYALL`, `KEYMS`)
     */
    public static function clang(
        ClangTarget $target = ClangTarget::All,
        bool $isMsExtensionsEnabled = false,
    ): self {
        $features = [...self::GCC_FEATURES, ...self::CLANG_FEATURES];
        $typedefNames = [...self::CLANG_TYPEDEF_NAMES, ...$target->getTypedefNames()];

        if ($isMsExtensionsEnabled) {
            $features = [...$features, ...self::CLANG_MS_FEATURES];
            $typedefNames = [...$typedefNames, ...self::MSVC_TYPEDEF_NAMES];
        }

        return new self($features, $typedefNames);
    }

    /**
     * Creates the preset of MSVC: the Microsoft extensions and the typedef
     * names of the sized integers and the wide character type.
     *
     * @link https://learn.microsoft.com/en-us/cpp/cpp/microsoft-specific-modifiers MSVC, Microsoft-specific modifiers
     * @link https://learn.microsoft.com/en-us/cpp/cpp/int8-int16-int32-int64 MSVC, `__int8`, `__int16`, `__int32`, `__int64`
     * @link https://learn.microsoft.com/en-us/cpp/cpp/char-wchar-t-char8-t-char16-t-char32-t MSVC, `__wchar_t`
     */
    public static function msvc(): self
    {
        return new self(self::MSVC_FEATURES, self::MSVC_TYPEDEF_NAMES);
    }

    /**
     * Creates the preset of PHP FFI, what {@see \FFI::cdef()} accepts: the
     * extensions of its own grammar and the typedef names of the fixed width
     * integers, the sizes and the variable argument lists.
     *
     * @link https://github.com/php/php-src/blob/master/ext/ffi/ffi.g PHP FFI, the grammar
     * @link https://github.com/php/php-src/blob/master/ext/ffi/ffi.c PHP FFI, the `zend_ffi_types` table
     */
    public static function php(): self
    {
        return new self(self::PHP_FFI_FEATURES, self::PHP_FFI_TYPEDEF_NAMES);
    }

    /**
     * Whether the rule rejects an extension one of the features accepts.
     *
     * @param ValidatorRuleInterface<covariant NodeInterface> $rule
     */
    public function isRuleRemoved(ValidatorRuleInterface $rule): bool
    {
        foreach ($this->features as $feature) {
            foreach ($feature->getRuleClasses() as $class) {
                if ($rule instanceof $class) {
                    return true;
                }
            }
        }

        return false;
    }
}
