<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Preset\ClangPreset;

use FFI\Parser\Preset\ClangPreset\ClangPresetArch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClangPresetArch::class)]
final class ClangPresetArchTest extends TestCase
{
    public function testEveryArchitectureListsTheNamesOfTheOtherCases(): void
    {
        $names = [];

        foreach (ClangPresetArch::cases() as $arch) {
            if ($arch !== ClangPresetArch::All) {
                $names = [...$names, ...$arch->getTypedefNames()];
            }
        }

        self::assertSame($names, ClangPresetArch::All->getTypedefNames());
    }
}
