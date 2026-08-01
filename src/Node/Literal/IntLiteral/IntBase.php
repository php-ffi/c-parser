<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

/**
 * Base of an integer constant. It matters beyond the value: a decimal
 * constant without a suffix never gets an unsigned type, while the other
 * ones do.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
enum IntBase: int
{
    /**
     * The `0b` or `0B` prefix.
     */
    case Binary = 2;

    /**
     * The `0` prefix, including the `0` constant itself.
     */
    case Octal = 8;

    /**
     * No prefix.
     */
    case Decimal = 10;

    /**
     * The `0x` or `0X` prefix.
     */
    case Hexadecimal = 16;

    /**
     * The base of a constant built without the source text.
     */
    public const DEFAULT = self::Decimal;
}
