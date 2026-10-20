<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\RecordType;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Member of a structure or a union, `int *a;` or the bit-field `unsigned b : 3;`.
 * Each declarator of a member declaration gives a node sharing the type of the specifiers.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 */
final class MemberDeclaration extends SpecifiedDeclaration
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
         * The name, {@see null} for an anonymous member or an unnamed bit-field
         */
        public ?Identifier $name = null,
        /**
         * The constant expression of the bit-field width
         */
        public ?ExpressionInterface $width = null,
        public ?AttributeList $attributes = null,
        ?SpecifierList $specifiers = null,
        int $offset = 0,
    ) {
        parent::__construct($specifiers, $offset);
    }
}
