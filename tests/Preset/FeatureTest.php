<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Preset;

use FFI\Parser\Exception\ParseRuntimeException;
use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Exception\ValidationException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Preset\Feature;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Feature::class)]
final class FeatureTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{
     *     Feature,
     *     non-empty-string,
     *     class-string<ParseRuntimeException>,
     *     3?: list<Feature>,
     * }>
     */
    public static function featuresDataProvider(): iterable
    {
        yield 'GNU attributes' => [
            Feature::GnuAttributes,
            'int f(void) __attribute__((noreturn));',
            SyntaxErrorException::class,
        ];
        yield 'assembler labels' => [Feature::AsmLabels, 'int f(void) __asm__("_f");', SyntaxErrorException::class];
        yield 'GNU keyword spellings' => [
            Feature::GnuKeywordSpellings,
            'int f(const char *__restrict s);',
            SyntaxErrorException::class,
        ];
        yield 'extension keyword' => [
            Feature::ExtensionKeyword,
            '__extension__ typedef long long T;',
            SyntaxErrorException::class,
        ];
        yield 'MSVC attributes' => [
            Feature::DeclSpec,
            '__declspec(dllimport) int f(void);',
            SyntaxErrorException::class,
        ];
        yield 'calling conventions' => [
            Feature::CallingConventions,
            'int __stdcall f(void);',
            SyntaxErrorException::class,
        ];
        yield 'trailing calling conventions' => [
            Feature::TrailingCallingConventions,
            'int f(void) __cdecl;',
            SyntaxErrorException::class,
            [Feature::CallingConventions],
        ];
        yield 'pointer modifiers' => [Feature::PointerModifiers, 'int * __ptr64 p;', SyntaxErrorException::class];
        yield 'force inline' => [Feature::ForceInline, 'static __forceinline int f(void);', SyntaxErrorException::class];
        yield 'MSVC integer suffixes' => [Feature::MsIntegerSuffixes, 'long long x = 1i64;', SyntaxErrorException::class];
        yield 'optional last member semicolon' => [
            Feature::OptionalLastMemberSemicolon,
            'struct S { int a };',
            SyntaxErrorException::class,
        ];
        yield 'keyword identifiers' => [Feature::KeywordIdentifiers, 'enum { false, true };', SyntaxErrorException::class];
        yield 'empty records' => [Feature::EmptyRecords, 'struct S {};', SyntaxErrorException::class];
        yield 'extra semicolons' => [Feature::ExtraSemicolons, 'int a;;', SyntaxErrorException::class];
        yield 'alignof of expressions' => [
            Feature::AlignOfExpressions,
            'int a; int x = __alignof__ a;',
            ValidationException::class,
            [Feature::GnuKeywordSpellings],
        ];
        yield 'type arguments of built-in functions' => [
            Feature::BuiltinTypeArguments,
            'int x = __builtin_offsetof(struct S, a);',
            ValidationException::class,
        ];
        yield 'statement expressions' => [
            Feature::StatementExpressions,
            'int x = ({ 1; });',
            SyntaxErrorException::class,
        ];
        yield 'complex integers' => [Feature::ComplexIntegers, '_Complex int x;', ValidationException::class];
        yield 'modified typedef names' => [
            Feature::ModifiedTypedefNames,
            'typedef long T; T unsigned x;',
            ValidationException::class,
        ];
        yield 'GNU flexible array members' => [
            Feature::GnuFlexibleArrayMembers,
            'union U { int a[]; };',
            ValidationException::class,
        ];
        yield 'tagged anonymous members' => [
            Feature::TaggedAnonymousMembers,
            'struct S { struct T; };',
            ValidationException::class,
        ];
        yield 'imaginary constants' => [
            Feature::ImaginaryConstants,
            '_Complex double x = 2.0i;',
            SyntaxErrorException::class,
        ];
        yield 'suffix of __float128' => [Feature::Float128Suffix, 'long double x = 1.0q;', SyntaxErrorException::class];
        yield 'suffix of __float80' => [Feature::Float80Suffix, 'long double x = 1.0w;', SyntaxErrorException::class];
        yield 'escape character' => [Feature::EscapeE, 'char *s = "\\e[0m";', SyntaxErrorException::class];
        yield 'range designators' => [
            Feature::RangeDesignators,
            'int a[] = {[0 ... 3] = 1};',
            SyntaxErrorException::class,
        ];
        yield 'omitted conditional operand' => [
            Feature::OmittedConditionalOperand,
            'int a, b; int x = a ?: b;',
            SyntaxErrorException::class,
        ];
        yield 'complex parts' => [
            Feature::ComplexParts,
            '_Complex double z; double x = __real__ z;',
            SyntaxErrorException::class,
        ];
        yield 'assembler declarations' => [Feature::AsmDeclarations, '__asm__("nop");', SyntaxErrorException::class];
        yield 'empty enumerations' => [Feature::EmptyEnums, 'enum E {};', SyntaxErrorException::class];
        yield 'block pointers' => [Feature::BlockPointers, 'void (^b)(int);', SyntaxErrorException::class];
        yield 'nullability' => [Feature::Nullability, 'int * _Nonnull p;', SyntaxErrorException::class];
        yield 'pointer authentication' => [
            Feature::PointerAuth,
            'void * __ptrauth(1, 0, 42) p;',
            SyntaxErrorException::class,
        ];
        yield 'based pointers' => [
            Feature::BasedPointers,
            'void *base; void __based(base) *p;',
            SyntaxErrorException::class,
        ];
        yield 'MSVC keyword spellings' => [
            Feature::MsKeywordSpellings,
            'static __inline int f(void) { return 0; }',
            SyntaxErrorException::class,
        ];
        yield 'trailing declspec' => [
            Feature::TrailingDeclSpec,
            'int f(void) __declspec(dllimport);',
            SyntaxErrorException::class,
            [Feature::DeclSpec],
        ];
        yield 'calling conventions among the specifiers' => [
            Feature::SpecifierCallingConventions,
            '__cdecl int f(void);',
            SyntaxErrorException::class,
            [Feature::CallingConventions],
        ];
        yield 'thiscall' => [Feature::ThisCallConvention, 'int __thiscall f(void);', SyntaxErrorException::class];
        yield 'calling conventions of MSVC' => [
            Feature::MsCallingConventions,
            'int __preserve_none f(void);',
            SyntaxErrorException::class,
        ];
        yield 'calling conventions of Clang' => [
            Feature::ClangCallingConventions,
            'int __regcall f(void);',
            SyntaxErrorException::class,
        ];
        yield 'unknown escape sequences' => [Feature::UnknownEscapes, 'char *s = "\\q";', SyntaxErrorException::class];
        yield 'octal prefix' => [Feature::OctalPrefix, 'int x = 0o17;', SyntaxErrorException::class];
        yield 'type name of a generic selection' => [
            Feature::GenericTypeOperand,
            'int x = _Generic(int, int: 1);',
            ValidationException::class,
        ];
    }

    /**
     * @param class-string<ParseRuntimeException> $exception
     * @param list<Feature> $required
     */
    #[DataProvider('featuresDataProvider')]
    public function testExtensionIsAcceptedWithItsFeatureOnly(
        Feature $feature,
        string $code,
        string $exception,
        array $required = [],
    ): void {
        self::assertNotSame([], (new Parser(preset: new Preset([...$required, $feature])))->parse($code));

        $this->expectException($exception);

        (new Parser(preset: new Preset($required)))->parse($code);
    }

    public function testEveryFeatureHasAnExample(): void
    {
        $features = [];

        foreach (self::featuresDataProvider() as $case) {
            $features[] = $case[0];
        }

        self::assertSame(Feature::cases(), $features);
    }

    public function testEveryExtensionRuleBelongsToOneFeature(): void
    {
        $classes = [];

        foreach (Feature::cases() as $feature) {
            foreach ($feature->getRuleClasses() as $class) {
                $classes[] = $class;
            }
        }

        $files = \glob(__DIR__ . '/../../src/Validator/Rule/Extension/*.php');

        self::assertIsArray($files);
        self::assertEqualsCanonicalizing(
            \array_map(
                static fn(string $file): string => 'FFI\\Parser\\Validator\\Rule\\Extension\\' . \basename($file, '.php'),
                $files,
            ),
            $classes,
        );
    }
}
