<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Declaration without declarators whose type is not a bare structure, union
 * or enumeration, such as `int;` or `static const long;`. It declares
 * nothing, which compilers warn about, but it is syntactically valid. A
 * qualified structure, union or enumeration with a body still declares its
 * tag: `const struct S { int a; };`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
final class TypeDeclaration extends SpecifiedDeclaration implements RootDeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the specifiers, {@see null} in case of no type
         * specifier and no qualifier is written, such as in `static;`
         */
        public readonly ?TypeInterface $type,
        SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
