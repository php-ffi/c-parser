<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral;

/**
 * Integer constant written in binary, after `0b` or `0B`, such as `0b101010`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
final class BinIntLiteral extends IntLiteral
{
    protected function representValue(): string
    {
        return '0b' . \decbin($this->value);
    }
}
