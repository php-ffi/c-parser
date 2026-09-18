<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\RecordType;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Member of a structure or a union, such as `int *a;` or the bit-field
 * `unsigned b : 3;`. A member declaration with several declarators gives a
 * node for each of them, and all of them start where the specifiers of the
 * member declaration do and share the type of the specifiers: in
 * `int a, *b;` the pointer of `b` points to the type of `a`. C allows no
 * storage-class and no function specifier in a member, but the parser
 * accepts them and keeps them for a validator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 */
final class MemberDeclaration extends SpecifiedDeclaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the member, built of the specifiers and the
         * declarator, {@see null} in case of no type specifier, no
         * qualifier and no pointer, array or function is written, such as
         * in `static (a);`
         */
        public ?TypeInterface $type,
        /**
         * The name, {@see null} in case of the member declaration has no
         * declarator (an anonymous structure or union, or a declaration of
         * nothing such as `int;`) or the member is an unnamed bit-field
         */
        public ?Identifier $name = null,
        /**
         * The constant expression of the width of a bit-field, {@see null}
         * in case of the member is not a bit-field
         */
        public ?ExpressionInterface $width = null,
        /**
         * The attributes of the declarator: written before it (GCC allows
         * them before any declarator but the first), after its name and
         * after the whole of it, the width of a bit-field included,
         * {@see null} in case of none is written
         */
        public ?AttributeList $attributes = null,
        SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
