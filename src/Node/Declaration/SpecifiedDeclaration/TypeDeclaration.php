<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Declaration of the specifiers alone, without declarators, whose type is
 * not a structure, union or enumeration by itself, such as `int;`,
 * `static const long;`, `typedef int;` or the qualified tag
 * `const struct S;`. Unless it declares a tag, C does not allow it
 * (6.7.1p2), but it is syntactically valid, and compilers only warn about
 * it.
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
