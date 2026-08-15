<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

/**
 * Type of a type name, `typeof_unqual(const int)`, which drops its
 * qualifiers. A lone identifier in the parentheses is read as a type name,
 * the parser has no table of the declared names to tell it from a variable.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.6 Typeof specifiers
 */
final class TypeOfUnqualTypeType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type name in the parentheses
         */
        public readonly TypeInterface $type,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
