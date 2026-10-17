<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

/**
 * What a compiler knows before the header: the names it declares itself.
 */
interface PresetInterface
{
    /**
     * Lists the typedef names built into the compiler: the identifiers the
     * compiler reads as typedef names without a declaration in the header,
     * such as `__builtin_va_list` or `__int64`.
     *
     * @return list<non-empty-string>
     */
    public function getTypedefNames(): array;
}
