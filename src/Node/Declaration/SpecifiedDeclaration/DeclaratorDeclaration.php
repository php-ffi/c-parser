<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\AsmLabel;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Initializer\InitializerInterface;
use FFI\Parser\Node\Specifier\SpecifierList;

/**
 * Base of the declarations of a name by a declarator, `int *p = 0;`.
 * Each declarator gives a node sharing the type of the specifiers.
 * `typedef` gives a {@see DeclaratorDeclaration\TypeDefDeclaration},
 * a function type a {@see DeclaratorDeclaration\FunctionDeclaration},
 * anything else a {@see DeclaratorDeclaration\VariableDeclaration}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
abstract class DeclaratorDeclaration extends SpecifiedDeclaration implements RootDeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public Identifier $name,
        /**
         * Attributes after the declared name and the GCC ones around the declarator
         */
        public ?AttributeList $attributes = null,
        public ?AsmLabel $asmLabel = null,
        public ?InitializerInterface $initializer = null,
        ?SpecifierList $specifiers = null,
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
