<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\DeclarationInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Type\RecordType\MemberDeclarationList;

/**
 * Structure or union type, `struct S { int a; }`. A declaration as well.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 */
abstract class RecordType extends Type implements DeclarationInterface
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
         * The members, {@see null} if there is no body, `struct S;`
         */
        public ?MemberDeclarationList $members = null,
        /**
         * Attributes after `struct` or `union`, `struct [[a]] S { int x; }`
         */
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
