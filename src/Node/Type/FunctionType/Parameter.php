<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\FunctionType;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\Type;

/**
 * Parameter of a function type, such as `const char *s` or the unnamed
 * `int`. A parameter with an array or a function type keeps it as written,
 * the adjustment to a pointer is up to the consumer. C allows only
 * `register` among the other specifiers of a parameter, but the parser
 * accepts any of them, as Clang does, and keeps them for a validator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.4 Function declarators
 */
final class Parameter extends SpecifiedDeclaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the parameter, {@see null} in case of no type
         * specifier and no qualifier is written, such as in `f(register)`
         */
        public readonly ?Type $type,
        /**
         * The name, {@see null} in case of the declarator is abstract or
         * there is none
         */
        public readonly ?Identifier $name = null,
        SpecifierList $specifiers = new SpecifierList([]),
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
