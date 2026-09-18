<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Initializer\InitializerInterface;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\FunctionType;

/**
 * Declaration of a function, its prototype `int printf(const char *, ...);`.
 * A declaration with a body is a {@see FunctionDefinition}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.4 Function declarators
 */
final class FunctionDeclaration extends DeclaratorDeclaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the function
         */
        public FunctionType $type,
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
