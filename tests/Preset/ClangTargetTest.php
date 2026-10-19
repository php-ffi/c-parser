<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Preset;

use FFI\Parser\Preset\ClangTarget;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ClangTarget::class)]
final class ClangTargetTest extends TestCase
{
    public function testAllTargetsListTheNamesOfTheOtherCases(): void
    {
        $names = [];

        foreach (ClangTarget::cases() as $target) {
            if ($target !== ClangTarget::All) {
                $names = [...$names, ...$target->getTypedefNames()];
            }
        }

        self::assertSame($names, ClangTarget::All->getTypedefNames());
    }
}
