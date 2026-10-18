<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Preset;

use FFI\Parser\Preset\CompoundPreset;
use FFI\Parser\Preset\PresetInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompoundPreset::class)]
final class CompoundPresetTest extends TestCase
{
    public function testNamesOfThePresetsAreListedInTheirOrderOnce(): void
    {
        $preset = new CompoundPreset([
            self::createPreset(['a', 'b']),
            self::createPreset(['c', 'a']),
            self::createPreset([]),
            self::createPreset(['b', 'd']),
        ]);

        self::assertSame(['a', 'b', 'c', 'd'], $preset->getTypedefNames());
    }

    /**
     * @param list<non-empty-string> $names
     */
    private static function createPreset(array $names): PresetInterface
    {
        return new class ($names) implements PresetInterface {
            /**
             * @param list<non-empty-string> $names
             */
            public function __construct(
                private readonly array $names,
            ) {}

            public function getTypedefNames(): array
            {
                return $this->names;
            }
        };
    }
}
