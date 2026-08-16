<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Pointer type, written by the `*` of a declarator: `int *p`. The qualifiers
 * of the pointer itself (`int *const p`) go to a {@see QualifiedType} around
 * this node.
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
         * The referenced type, {@see null} in case of the specifiers give no
         * type, such as in `static *p;`
         */
        public readonly ?TypeInterface $type,
        /**
         * The attributes written after the `*`, `*[[x]]` or `*_Nonnull`,
         * {@see null} in case of none is written
         */
        public readonly ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
