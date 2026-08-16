<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\DeclarationInterface;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Type\RecordType\MemberDeclarationList;

/**
 * Structure or union type, such as `struct S { int a; }`. The kind is the
 * class of the node. It is a declaration as well: with a body it declares
 * its tag, and without one it may declare an incomplete type (6.7.3.4p12).
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
         * The tag, {@see null} in case of an anonymous one
         */
        public readonly ?Identifier $name,
        /**
         * The members, {@see null} in case of there is no body: a reference
         * to the tag or its declaration without the members, such as
         * `struct S;`
         */
        public readonly ?MemberDeclarationList $members = null,
        /**
         * The attributes after `struct` or `union` and, as GCC allows,
         * after the body, `struct [[a]] S { int x; } __attribute__((b))`.
         * The ones after a tag without a body, `struct S [[a]];`, are among
         * the specifiers. {@see null} in case of none is written
         */
        public readonly ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
