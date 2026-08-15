<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\EnumType;
use FFI\Parser\Node\Type\RecordType;

/**
 * Declaration without declarators whose type is a structure, union or
 * enumeration by itself, not wrapped into qualifiers or `_Atomic(T)`, such
 * as `struct S { int a; };`, `struct S;` or `static struct S;`. The other
 * specifiers are kept in the list: the form of 6.7.3.4p11, which declares
 * a new type even if an outer scope has the tag, allows none of them, and
 * for an enumeration requires the underlying type, `enum E : int;`. A
 * qualified tag (`const struct S;`) is a {@see TypeDeclaration}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.4 Tags
 */
final class TagDeclaration extends SpecifiedDeclaration implements RootDeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The declared type
         */
        public readonly RecordType|EnumType $type,
        SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
