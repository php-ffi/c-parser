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
 * Base of the declarations of a name by a declarator, `int *p = 0;`. A
 * declaration with several declarators gives a node for each of them, and
 * all of them start where the specifiers do and share the type of the
 * specifiers: in `int a, *b;` the pointer of `b` points to the type of `a`.
 * The kind of the declaration is chosen by one sign and hides nothing else:
 * `typedef` among the specifiers gives a {@see DeclaratorDeclaration\TypeDefDeclaration},
 * a function type a {@see DeclaratorDeclaration\FunctionDeclaration}, anything
 * else a {@see DeclaratorDeclaration\VariableDeclaration}. Each kind has its
 * own type property: a function has a {@see \FFI\Parser\Node\Type\FunctionType}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
abstract class DeclaratorDeclaration extends SpecifiedDeclaration implements RootDeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The declared name
         */
        public Identifier $name,
        /**
         * The attributes written after the declared name, and the GCC
         * attributes before the declarator and after the whole of it,
         * {@see null} in case of none is written
         */
        public ?AttributeList $attributes = null,
        /**
         * The name of the symbol in the object file, {@see null} in case of
         * none is written
         */
        public ?AsmLabel $asmLabel = null,
        /**
         * The initializer after `=`, {@see null} in case of none is written.
         * C allows it only in the declaration of an object, but the parser
         * accepts it after any declarator and keeps it for a validator
         */
        public ?InitializerInterface $initializer = null,
        ?SpecifierList $specifiers = null,
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
