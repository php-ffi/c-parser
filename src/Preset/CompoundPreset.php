<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

use FFI\Parser\Preset;

/**
 * Union of the presets: whatever any of them knows.
 */
final class CompoundPreset extends Preset
{
    /**
     * @var list<non-empty-string>
     */
    private array $typedefNames;

    public function __construct(
        /**
         * Presets whose names are merged
         *
         * @var non-empty-list<PresetInterface>
         */
        private readonly array $presets,
    ) {}

    /**
     * {@inheritDoc}
     *
     * The names of every preset in the order the presets are given, each
     * name once.
     */
    public function getTypedefNames(): array
    {
        return $this->typedefNames ??= $this->mergeTypedefNames();
    }

    /**
     * @return list<non-empty-string>
     */
    private function mergeTypedefNames(): array
    {
        $names = [];

        foreach ($this->presets as $preset) {
            foreach ($preset->getTypedefNames() as $name) {
                $names[$name] = true;
            }
        }

        return \array_keys($names);
    }
}
