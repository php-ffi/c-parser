<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\EnumType;
use FFI\Parser\Node\Type\RecordType;

/**
 * Declaration without declarators of a structure, union or enumeration by itself,
 * `struct S { int a; };` or `struct S;`.
 * A qualified tag is a {@see TypeDeclaration}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.4 Tags
 */
final class TagDeclaration extends SpecifiedDeclaration implements RootDeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public RecordType|EnumType $type,
        ?SpecifierList $specifiers = null,
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
