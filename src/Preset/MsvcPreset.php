<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

use FFI\Parser\Preset;

/**
 * Typedef names of MSVC: the sized integers and the wide character type,
 * keywords of MSVC spelled as identifiers in C.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/int8-int16-int32-int64 MSVC, `__int8`, `__int16`, `__int32`, `__int64`
 * @link https://learn.microsoft.com/en-us/cpp/cpp/char-wchar-t-char8-t-char16-t-char32-t MSVC, `__wchar_t`
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/TokenKinds.def Clang, the keywords of the Microsoft extensions (`KEYMS`)
 */
final class MsvcPreset extends Preset
{
    /**
     * @var list<non-empty-string>
     */
    private const TYPEDEF_NAMES = [
        '__int8',
        '__int16',
        '__int32',
        '__int64',
        '_int8',
        '_int16',
        '_int32',
        '_int64',
        '__wchar_t',
    ];

    public function getTypedefNames(): array
    {
        return self::TYPEDEF_NAMES;
    }
}
