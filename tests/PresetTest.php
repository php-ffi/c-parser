<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\ParserException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Preset\ClangTarget;
use FFI\Parser\Preset\Feature;
use FFI\Parser\Validator;
use FFI\Parser\Validator\Rule\Extension\ComplexIntegerTypeRule;
use FFI\Parser\Validator\Rule\TypeSpecifierRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Preset::class)]
final class PresetTest extends TestCase
{
    public function testC23PresetRemovesNoRules(): void
    {
        $preset = Preset::c23();

        foreach (Validator::getDefaultRules() as $rule) {
            self::assertFalse($preset->isRuleRemoved($rule));
        }
    }

    public function testDefaultPresetAcceptsEveryFeature(): void
    {
        self::assertSame(Feature::cases(), Preset::default()->features);
    }

    public function testDefaultPresetKnowsTheTypedefNamesOfEveryCompilerOnce(): void
    {
        $names = Preset::default()->typedefNames;
        $compilers = [
            ...Preset::gcc()->typedefNames,
            ...Preset::clang()->typedefNames,
            ...Preset::msvc()->typedefNames,
            ...Preset::php()->typedefNames,
        ];

        self::assertSame(\array_values(\array_unique($names)), $names);
        self::assertSame([], \array_diff($compilers, $names));
    }

    public function testFeatureRemovesItsRulesOnly(): void
    {
        $preset = new Preset([Feature::ComplexIntegers]);

        self::assertSame(
            [true, false],
            [$preset->isRuleRemoved(new ComplexIntegerTypeRule()), $preset->isRuleRemoved(new TypeSpecifierRule())],
        );
    }

    /**
     * @return iterable<non-empty-string, array{Preset}>
     */
    public static function presetsDataProvider(): iterable
    {
        yield 'GCC' => [Preset::gcc()];
        yield 'Clang' => [Preset::clang()];
        yield 'Clang with the Microsoft extensions' => [Preset::clang(isMsExtensionsEnabled: true)];
        yield 'MSVC' => [Preset::msvc()];
        yield 'PHP FFI' => [Preset::php()];
    }

    #[DataProvider('presetsDataProvider')]
    public function testPresetListsEveryFeatureOnce(Preset $preset): void
    {
        $names = \array_map(static fn(Feature $feature): string => $feature->name, $preset->features);

        self::assertSame(\array_values(\array_unique($names)), $names);
    }

    #[DataProvider('presetsDataProvider')]
    public function testPresetWithTrailingCallingConventionsHasTheConventions(Preset $preset): void
    {
        self::assertTrue(
            !\in_array(Feature::TrailingCallingConventions, $preset->features, true)
            || \in_array(Feature::CallingConventions, $preset->features, true),
        );
    }

    /**
     * @return iterable<non-empty-string, array{Preset, non-empty-string}>
     */
    public static function acceptedCodeDataProvider(): iterable
    {
        yield 'GCC' => [
            Preset::gcc(),
            '__extension__ typedef unsigned __int128 u128; int f(void) __asm__("_f") __attribute__((pure));',
        ];
        yield 'GCC with the second wave' => [
            Preset::gcc(),
            '__asm__("nop"); int a[] = {[0 ... 1] = 2}; long double x = 1.0w; int y = a ?: 1;',
        ];
        yield 'Clang' => [Preset::clang(), 'void __stdcall f(__builtin_va_list ap, __fp16 h);'];
        yield 'Clang with blocks and nullability' => [
            Preset::clang(),
            'void g(void (^ _Nonnull b)(int)); _Complex double z = 2.0i;',
        ];
        yield 'Clang with the Microsoft extensions' => [
            Preset::clang(isMsExtensionsEnabled: true),
            '__declspec(dllimport) unsigned __int64 * __ptr64 f(void); enum E {};',
        ];
        yield 'MSVC' => [
            Preset::msvc(),
            '__declspec(dllimport) unsigned __int64 __stdcall f(int * __ptr64 p); __int64 x = 1i64;'
                . ' char __based(p) *q;',
        ];
        yield 'PHP FFI' => [
            Preset::php(),
            'struct S { int64_t a }; size_t f(void) __cdecl; enum { false, true };',
        ];
    }

    /**
     * @param non-empty-string $code
     */
    #[DataProvider('acceptedCodeDataProvider')]
    public function testPresetAcceptsTheExtensionsOfItsCompiler(Preset $preset, string $code): void
    {
        self::assertNotSame([], (new Parser(preset: $preset))->parse($code));
    }

    /**
     * @return iterable<non-empty-string, array{Preset, non-empty-string}>
     */
    public static function rejectedCodeDataProvider(): iterable
    {
        yield 'GCC with a declspec' => [Preset::gcc(), '__declspec(dllimport) int x;'];
        yield 'GCC with a calling convention' => [Preset::gcc(), 'void __stdcall f(void);'];
        yield 'Clang with a declspec' => [Preset::clang(), '__declspec(dllimport) int x;'];
        yield 'Clang with a suffix of MSVC' => [Preset::clang(), 'int x = 1i64;'];
        yield 'Clang with the suffix of __float80' => [Preset::clang(), 'long double x = 1.0w;'];
        yield 'GCC with a block pointer' => [Preset::gcc(), 'void (^b)(int);'];
        yield 'MSVC with an empty enumeration' => [Preset::msvc(), 'enum E {};'];
        yield 'MSVC with a GNU attribute' => [Preset::msvc(), 'int x __attribute__((aligned(8)));'];
        yield 'PHP FFI with a statement expression' => [Preset::php(), 'int x = ({ 1; });'];
    }

    /**
     * @param non-empty-string $code
     */
    #[DataProvider('rejectedCodeDataProvider')]
    public function testPresetRejectsTheExtensionsOfOtherCompilers(Preset $preset, string $code): void
    {
        $this->expectException(ParserException::class);

        (new Parser(preset: $preset))->parse($code);
    }

    public function testClangPresetKnowsTheTypedefNamesOfItsTargetOnly(): void
    {
        $names = Preset::clang(ClangTarget::WebAssembly)->typedefNames;

        self::assertContains('__builtin_va_list', $names);
        self::assertContains('__externref_t', $names);
        self::assertNotContains('__vector_quad', $names);
    }
}
