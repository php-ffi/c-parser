<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

use FFI\Parser\Preset;

/**
 * Typedef names of GCC common to its targets: the variable argument lists,
 * the 128-bit integers and the extended floating types.
 *
 * @link https://github.com/gcc-mirror/gcc/blob/master/gcc/c-family/c-common.cc GCC, `c_common_nodes_and_builtins()`
 * @link https://github.com/gcc-mirror/gcc/blob/master/gcc/config/i386/i386-builtins.cc GCC, the types of x86
 * @link https://github.com/gcc-mirror/gcc/blob/master/gcc/config/i386/i386.cc GCC, the variable argument lists of x86-64
 * @link https://github.com/gcc-mirror/gcc/blob/master/gcc/config/aarch64/aarch64-builtins.cc GCC, the types of AArch64
 * @link https://github.com/gcc-mirror/gcc/blob/master/gcc/config/arm/arm-builtins.cc GCC, the types of ARM
 * @link https://github.com/gcc-mirror/gcc/blob/master/gcc/config/rs6000/rs6000-builtin.cc GCC, the types of PowerPC
 * @link https://gcc.gnu.org/onlinedocs/gcc/Floating-Types.html GCC, Additional Floating Types
 * @link https://gcc.gnu.org/onlinedocs/gcc/_005f_005fint128.html GCC, 128-bit Integers
 */
final class GccPreset extends Preset
{
    /**
     * @var list<non-empty-string>
     */
    private const TYPEDEF_NAMES = [
        '__builtin_va_list',
        '__builtin_ms_va_list',
        '__builtin_sysv_va_list',
        '__int128',
        '__int128__',
        '__int128_t',
        '__uint128_t',
        '__float80',
        '__float128',
        '__ibm128',
        '__ieee128',
        '__fp16',
        '__bf16',
    ];

    public function getTypedefNames(): array
    {
        return self::TYPEDEF_NAMES;
    }
}
