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
 * Declaration of an object, `extern const char *const names[16];`. A
 * function whose type is not written by its declarator gives this node as
 * well: the parser has no table of the declared names to tell `F f;` with a
 * typedef function type `F` from an object, and does not look into
 * `typeof(int (void)) f;`, nor into the attributes that wrap a function at
 * the start of a parenthesized declarator, `void (__stdcall f)(int);`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
final class VariableDeclaration extends DeclaratorDeclaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the object, built of the specifiers and the
         * declarator, {@see null} in case of the specifiers give no type
         * and the declarator is a name, such as in `static (x);` (without
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
