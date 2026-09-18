<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Literal\StringLiteral;

/**
 * Adjacent string literals, which C joins into one: `"a" "b"`.
 *
 * The parts are kept apart, each with its own prefix (`u8"a" L"b"` cannot
 * be joined).
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.5 String literals
 */
final class ConcatenatedStringExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The literals in the order they are written, two at least when built by the parser
         *
         * @var non-empty-list<StringLiteral>
         */
        public array $parts,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
