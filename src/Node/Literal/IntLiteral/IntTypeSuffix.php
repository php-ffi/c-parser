<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

/**
 * Suffix of an integer constant, named by the type it gives the constant.
 * Spellings of the same type (`ul` and `LU`) are one case, the exact
 * spelling is kept by the raw text of the constant. The value of a case is
 * the spelling a constant built without the source text gets.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 * @link https://learn.microsoft.com/en-us/cpp/c-language/c-integer-constants MSVC, integer constants
 */
enum IntTypeSuffix: string
{
    /**
     * `u` or `U`.
     */
    case Unsigned = 'u';

    /**
     * `l` or `L`.
     */
    case Long = 'l';

    /**
     * `u` and `l` in either order and case, such as `ul` or `LU`.
     */
    case UnsignedLong = 'ul';

    /**
     * `ll` or `LL`.
     */
    case LongLong = 'll';

    /**
     * `u` and `ll` in either order and case, such as `ull` or `LLU`.
     */
    case UnsignedLongLong = 'ull';

    /**
     * `wb` or `WB`.
     */
    case BitPrecise = 'wb';

    /**
     * `u` and `wb` in either order and case, such as `uwb` or `WBU`.
     */
    case UnsignedBitPrecise = 'uwb';

    /**
     * MSVC: `i8` or `I8`, the `__int8` type.
     */
    case Int8 = 'i8';

    /**
     * MSVC: `i16` or `I16`, the `__int16` type.
     */
    case Int16 = 'i16';

    /**
     * MSVC: `i32` or `I32`, the `__int32` type.
     */
    case Int32 = 'i32';

    /**
     * MSVC: `i64` or `I64`, the `__int64` type.
     */
    case Int64 = 'i64';

    /**
     * MSVC: `ui8` in any case, the `unsigned __int8` type.
     */
    case UnsignedInt8 = 'ui8';

    /**
     * MSVC: `ui16` in any case, the `unsigned __int16` type.
     */
    case UnsignedInt16 = 'ui16';

    /**
     * MSVC: `ui32` in any case, the `unsigned __int32` type.
     */
    case UnsignedInt32 = 'ui32';

    /**
     * MSVC: `ui64` in any case, the `unsigned __int64` type.
     */
    case UnsignedInt64 = 'ui64';
}
