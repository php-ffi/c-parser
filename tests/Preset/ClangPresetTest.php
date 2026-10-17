<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Preset;

use FFI\Parser\Preset\ClangPreset;
use FFI\Parser\Preset\ClangPreset\ClangPresetArch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClangPreset::class)]
final class ClangPresetTest extends TestCase
{
    public function testPresetListsTheNamesOfTheGivenArchitectureOnly(): void
    {
        $names = (new ClangPreset(ClangPresetArch::WebAssembly))->getTypedefNames();

        self::assertContains('__builtin_va_list', $names);
        self::assertContains('__externref_t', $names);
        self::assertNotContains('__vector_quad', $names);
    }
}
