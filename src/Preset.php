<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Preset\ClangPreset;
use FFI\Parser\Preset\CompoundPreset;
use FFI\Parser\Preset\GccPreset;
use FFI\Parser\Preset\MsvcPreset;
use FFI\Parser\Preset\PhpPreset;
use FFI\Parser\Preset\PresetInterface;

/**
 * Base of the presets of the compilers.
 */
abstract class Preset implements PresetInterface
{
    /**
     * Creates the preset of every supported compiler: Clang, MSVC, GCC and
     * PHP FFI.
     */
    public static function createDefault(): CompoundPreset
    {
        return new CompoundPreset(
            new ClangPreset(),
            new MsvcPreset(),
            new GccPreset(),
            new PhpPreset(),
        );
    }
}
