<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Type\QualifiedType\TypeQualifier;

/**
 * Type with qualifiers, such as `const volatile int` or `int *const`. The
 * qualifiers of the specifiers are gathered from anywhere among them, and
 * the node wraps the whole type of them, a {@see ModifiedType} included.
 * The qualifiers written after the `*` of a pointer wrap the
 * {@see PointerType}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.4 Type qualifiers
 */
final class QualifiedType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The qualifiers in the order written, repeats included
         *
         * @var non-empty-list<TypeQualifier>
         */
        public readonly array $qualifiers,
        /**
         * The qualified type, {@see null} in case of no type specifier is
         * written, such as in `const;`
         */
        public readonly ?TypeInterface $type = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
