<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Type\TypeInterface;

/**
 * The `sizeof` operator applied to a type: `sizeof(T)`.
 *
 * A single name in parentheses is read as a type unless a postfix operator
 * follows it (`sizeof(x)[0]` is the size of `(x)[0]`). Whether the name
 * actually names a type or a variable is up to the consumer.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.4.4 The sizeof and alignof operators
 */
final class SizeOfTypeExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type whose size is taken
         */
        public TypeInterface $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
