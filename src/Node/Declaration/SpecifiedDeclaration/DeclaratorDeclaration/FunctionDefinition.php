<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition\FunctionBody;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Initializer\InitializerList;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Definition of a function with its body, such as the `static inline`
 * functions of a header: `static inline int f(int x) { return x; }`. The
 * statements of the body are not parsed, the body keeps its text. PHP FFI
 * accepts no definitions, a consumer leaves them out or keeps their
 * declarations.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.9.1 Function definitions
 */
final class FunctionDefinition extends DeclaratorDeclaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the function, built of the specifiers and the
         * declarator. C requires a function type (6.9.1p2), but the parser
         * accepts a body after any declarator and keeps the type for a
         * validator, {@see null} in case of the specifiers give no type and
         * the declarator is a name
         */
        public readonly ?TypeInterface $type,
        Identifier $name,
        /**
         * The body in braces
         */
        public readonly FunctionBody $body,
        ?AttributeList $attributes = null,
        ?AsmLabel $asmLabel = null,
        ExpressionInterface|InitializerList|null $initializer = null,
        SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($name, $attributes, $asmLabel, $initializer, $specifiers, $offset);
    }
}
