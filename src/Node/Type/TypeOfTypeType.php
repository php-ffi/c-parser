<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

/**
 * Type of a type name, `typeof(const int)`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.6 Typeof specifiers
 */
final class TypeOfTypeType extends Type
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
