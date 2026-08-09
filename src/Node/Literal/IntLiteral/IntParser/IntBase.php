<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\IntParser;

/**
 * Base the digits of an integer constant are written in, told by the
 * prefix.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
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
}
