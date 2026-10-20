<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Type\TypeInterface;

/**
 * The `alignof` operator, also spelled `_Alignof`: `alignof(T)`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.4.4 The sizeof and alignof operators
 */
final class AlignOfTypeExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public TypeInterface $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
