<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

use FFI\Parser\Preset;

/**
 * Typedef names PHP FFI declares for {@see \FFI::cdef()}: the fixed width
 * integers, the sizes and the variable argument lists.
 *
 * @link https://github.com/php/php-src/blob/master/ext/ffi/ffi.c PHP FFI, the `zend_ffi_types` table
 */
final class PhpPreset extends Preset
{
    /**
     * @var list<non-empty-string>
     */
    private const TYPEDEF_NAMES = [
        'int8_t',
        'uint8_t',
        'int16_t',
        'uint16_t',
        'int32_t',
        'uint32_t',
        'int64_t',
        'uint64_t',
        'intptr_t',
        'uintptr_t',
        'size_t',
        'ssize_t',
        'ptrdiff_t',
        'off_t',
        'va_list',
        '__builtin_va_list',
        '__gnuc_va_list',
    ];

    public function getTypedefNames(): array
    {
        return self::TYPEDEF_NAMES;
    }
}
