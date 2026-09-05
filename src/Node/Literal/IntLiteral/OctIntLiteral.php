<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral;

/**
 * Integer constant written in octal, after `0`, such as `052`. The constant `0` is octal too.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
final class OctIntLiteral extends IntLiteral
{
    protected function representAsRawNumber(): string
    {
        return '0' . \decoct($this->value);
    }
}
