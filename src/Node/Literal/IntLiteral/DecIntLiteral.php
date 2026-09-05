<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral;

/**
 * Integer constant written in decimal, such as `42`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
final class DecIntLiteral extends IntLiteral
{
    protected function representAsRawNumber(): string
    {
        return $this->decimal;
    }
}
