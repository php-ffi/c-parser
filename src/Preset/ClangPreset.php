<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

use FFI\Parser\Preset;
use FFI\Parser\Preset\ClangPreset\ClangPresetArch;

/**
 * Typedef names of Clang: the variable argument lists, the 128-bit
 * integers, the extended floating types and the types of the target
 * architectures.
 *
 * @link https://github.com/llvm/llvm-project/blob/main/clang/lib/Sema/Sema.cpp Clang, `Sema::Initialize()`
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/TokenKinds.def Clang, the keywords of the types (`KEYALL`)
 */
final class ClangPreset extends Preset
{
    /**
     * @var list<non-empty-string>
     */
    private const TYPEDEF_NAMES = [
        '__builtin_va_list',
        '__builtin_ms_va_list',
        '__builtin_zos_va_list',
        '__int128',
        '__int128_t',
        '__uint128_t',
        '__float128',
        '__ibm128',
        '__fp16',
        '__bf16',
        '__NSConstantString',
    ];

    public function __construct(
        /**
         * Target architecture whose types are known
         */
        public readonly ClangPresetArch $arch = ClangPresetArch::All,
    ) {}

    public function getTypedefNames(): array
    {
        return [...self::TYPEDEF_NAMES, ...$this->arch->getTypedefNames()];
    }
}
