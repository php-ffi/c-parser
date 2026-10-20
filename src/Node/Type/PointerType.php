<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Pointer type, written by the `*` of a declarator, `int *p`.
 * The qualifiers of the pointer go to a {@see QualifiedType} around the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.2 Pointer declarators
 */
final class PointerType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The referenced type, {@see null} if the specifiers give no type
         */
        public ?TypeInterface $type,
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
