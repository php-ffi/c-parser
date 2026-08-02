<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\Expression\Expression;
use FFI\Parser\Node\Literal\ConcatenatedStringLiteral;
use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Node\Node;

/**
 * Compile-time assertion: `static_assert(condition, "message");`, also
 * spelled `_Static_assert`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.12 Static assertions
 */
final class StaticAssertDeclaration extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The constant expression asserted to be non-zero
         */
        public readonly Expression $condition,
        /**
         * The message reported when the assertion fails, {@see null} in case
         * of it is omitted, which C23 allows
         */
        public readonly StringLiteral|ConcatenatedStringLiteral|null $message = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
