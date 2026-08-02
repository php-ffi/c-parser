<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Identifier;

/**
 * Access to a member of a structure or a union, such as `object.member` or
 * `pointer->member`. The operator is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.4 Structure and union members
 */
abstract class MemberExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The structure or the union, or the pointer to it
         */
        public readonly Expression $object,
        /**
         * The name of the member
         */
        public readonly Identifier $member,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
