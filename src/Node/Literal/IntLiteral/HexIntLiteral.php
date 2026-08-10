<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral;

/**
 * Integer constant written in hexadecimal, after `0x` or `0X`, such as `0x2A`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
final class HexIntLiteral extends IntLiteral
{
    protected function representValue(): string
    {
        return '0x' . \dechex($this->value);
    }
}
