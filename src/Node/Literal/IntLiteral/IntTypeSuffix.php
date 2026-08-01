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
}
