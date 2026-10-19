<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\DeclarationInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Type\EnumType\EnumeratorList;

/**
 * Enumeration type, such as `enum E : unsigned char { A, B = 2 }`. It is a
 * declaration as well: with a body it declares its tag and its constants.
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
         * The tag, {@see null} in case of an anonymous one
         */
        public ?Identifier $name,
        /**
         * The fixed underlying type written after the colon, {@see null}
         * in case of there is none
         */
        public ?TypeInterface $underlyingType = null,
        /**
         * The enumerators, {@see null} in case of there is no body: a
         * reference to the tag or its declaration without the enumerators,
         * such as `enum E : int;`
         */
        public ?EnumeratorList $enumerators = null,
        /**
         * The attributes after `enum`, `enum [[a]] E { A }`. The ones
         * after a tag without a body, `enum E [[a]];`, are among the
         * specifiers. {@see null} in case of none is written
         */
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
