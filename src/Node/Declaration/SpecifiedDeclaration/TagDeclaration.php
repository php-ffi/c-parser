<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\EnumType;
use FFI\Parser\Node\Type\RecordType;

/**
 * Declaration without declarators whose type is a bare structure, union or
 * enumeration, such as `struct S { int a; };` or `enum E : int;`. A
 * qualified one (`const struct S { int a; };`) is a {@see TypeDeclaration},
 * though it declares the tag as well.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.4 Tags
 */
final class TagDeclaration extends SpecifiedDeclaration
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
