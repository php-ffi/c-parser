<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

/**
 * Base the mantissa of a floating constant is written in. It also decides
 * what the exponent raises: 10 for a decimal constant and 2 for a
 * hexadecimal one.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 */
enum FloatBase: int
{
    /**
     * No prefix, the exponent is written after `e` or `E`.
     */
    case Decimal = 10;

    /**
     * The `0x` or `0X` prefix, the exponent is written after `p` or `P`.
     */
    case Hexadecimal = 16;

    /**
     * The base of a constant built without the source text.
     */
    public const DEFAULT = self::Decimal;
}
