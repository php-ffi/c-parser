<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Initializer\InitializerInterface;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Declaration of a typedef name, `typedef void (*callback_t)(int);`: a
 * declaration with `typedef` among its specifiers. The other specifiers
 * stay in the list: `typedef static int x;` is kept as written.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.9 Type definitions
 */
final class TypeDefDeclaration extends DeclaratorDeclaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type the name stands for, built of the specifiers and the
         * declarator, {@see null} in case of the specifiers give no type
         * and the declarator is a name, such as in `typedef (x);` (without
         * the parentheses `x` is the typedef name of the type)
         */
        public ?TypeInterface $type,
        Identifier $name,
        ?AttributeList $attributes = null,
        ?AsmLabel $asmLabel = null,
        ?InitializerInterface $initializer = null,
        SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($name, $attributes, $asmLabel, $initializer, $specifiers, $offset);
    }
}
