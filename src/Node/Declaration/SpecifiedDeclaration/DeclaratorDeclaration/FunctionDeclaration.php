<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Initializer\InitializerList;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\FunctionType;

/**
 * Declaration of a function, its prototype `int printf(const char *, ...);`.
 * A definition with a body is not a part of a header and is a syntax error.
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
        public readonly FunctionType $type,
        Identifier $name,
        ExpressionInterface|InitializerList|null $initializer = null,
        SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($name, $initializer, $specifiers, $offset);
    }
}
