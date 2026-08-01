<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\StringLiteral;

/**
 * Prefix of a character constant or a string literal, named by the
 * encoding it selects along with the character type.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.5 Character constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.5 String literals
 */
enum StringTypePrefix: string
{
    /**
     * `u8`: the `char8_t` type, UTF-8.
     */
    case Utf8 = 'u8';

    /**
     * `u`: the `char16_t` type, UTF-16.
     */
    case Utf16 = 'u';

    /**
     * `U`: the `char32_t` type, UTF-32.
     */
    case Utf32 = 'U';

    /**
     * `L`: the `wchar_t` type.
     */
    case Wide = 'L';
}
