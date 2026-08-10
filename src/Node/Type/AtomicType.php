<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

/**
 * Atomic type written by the type specifier, `_Atomic(T)`. The `_Atomic`
 * qualifier without the parentheses is a {@see QualifiedType}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.5 Atomic type specifiers
 */
final class AtomicType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type made atomic
         */
        public readonly Type $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
