<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\DeclarationInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Type\EnumType\EnumeratorList;

/**
 * Enumeration type, `enum E : unsigned char { A, B = 2 }`. A declaration as well.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.3 Enumeration specifiers
 */
final class EnumType extends Type implements DeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The tag, {@see null} if anonymous
         */
        public ?Identifier $name,
        /**
         * The fixed underlying type after the colon
         */
        public ?TypeInterface $underlyingType = null,
        /**
         * The enumerators, {@see null} if there is no body, `enum E : int;`
         */
        public ?EnumeratorList $enumerators = null,
        /**
         * Attributes after `enum`, `enum [[a]] E { A }`
         */
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
