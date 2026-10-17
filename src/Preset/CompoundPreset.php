<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

use FFI\Parser\Preset;

/**
 * Union of the presets: whatever any of them knows. Without presets, it is
 * the empty preset.
 */
final class CompoundPreset extends Preset
{
    /**
     * @var list<non-empty-string>
     */
    private readonly array $typedefNames;

    public function __construct(PresetInterface ...$presets)
    {
        $this->typedefNames = self::mergeTypedefNames($presets);
    }

    /**
     * @param array<array-key, PresetInterface> $presets
     * @return list<non-empty-string>
     */
    private static function mergeTypedefNames(array $presets): array
    {
        $names = [];

        foreach ($presets as $preset) {
            foreach ($preset->getTypedefNames() as $name) {
                $names[$name] = true;
            }
        }

        return \array_keys($names);
    }

    /**
     * {@inheritDoc}
     *
     * The names of every preset in the order the presets are given, each
     * name once.
     */
    public function getTypedefNames(): array
    {
        return $this->typedefNames;
    }
}
