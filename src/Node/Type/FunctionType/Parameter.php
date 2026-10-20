<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\FunctionType;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Parameter of a function type, such as `const char *s` or the unnamed `int`.
 * Array and function types are kept as written, without the adjustment to a pointer.
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
         * The type built of the specifiers and the declarator, {@see null} if none is written
         */
        public ?TypeInterface $type,
        /**
         * The name, {@see null} if the declarator is abstract
         */
        public ?Identifier $name = null,
        public ?AttributeList $attributes = null,
        ?SpecifierList $specifiers = null,
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
