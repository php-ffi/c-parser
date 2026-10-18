<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\TypeDefDeclaration;
use FFI\Parser\Preset;
use FFI\Parser\Preset\ClangPreset;
use FFI\Parser\Preset\ClangPreset\ClangPresetArch;
use FFI\Parser\Preset\CompoundPreset;
use FFI\Parser\Preset\GccPreset;
use FFI\Parser\Preset\MsvcPreset;
use FFI\Parser\Preset\PhpPreset;
use FFI\Parser\Preset\PresetInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Preset::class)]
#[CoversClass(ClangPreset::class)]
#[CoversClass(ClangPresetArch::class)]
#[CoversClass(CompoundPreset::class)]
#[CoversClass(GccPreset::class)]
#[CoversClass(MsvcPreset::class)]
#[CoversClass(PhpPreset::class)]
final class PresetTest extends TestCase
{
    public function testDefaultPresetListsTheNamesOfEveryCompiler(): void
    {
        $expected = new CompoundPreset([
            new ClangPreset(),
            new MsvcPreset(),
            new GccPreset(),
            new PhpPreset(),
        ]);

        self::assertSame($expected->getTypedefNames(), Preset::createDefault()->getTypedefNames());
    }

    /**
     * @return iterable<non-empty-string, array{PresetInterface}>
     */
    public static function presetDataProvider(): iterable
    {
        yield 'Clang' => [new ClangPreset()];
        yield 'MSVC' => [new MsvcPreset()];
        yield 'GCC' => [new GccPreset()];
        yield 'PHP' => [new PhpPreset()];
    }

    #[DataProvider('presetDataProvider')]
    public function testEveryTypedefNameIsReadAsAnIdentifier(PresetInterface $preset): void
    {
        $code = '';

        foreach ($preset->getTypedefNames() as $name) {
            $code .= "typedef int {$name};\n";
        }

        $names = [];

        foreach (self::parse($code) as $declaration) {
            self::assertInstanceOf(TypeDefDeclaration::class, $declaration);

            $names[] = $declaration->name->value;
        }

        self::assertSame($preset->getTypedefNames(), $names);
    }

    #[DataProvider('presetDataProvider')]
    public function testEveryTypedefNameIsListedOnce(PresetInterface $preset): void
    {
        $names = $preset->getTypedefNames();

        self::assertSame($names, \array_values(\array_unique($names)));
    }
}
