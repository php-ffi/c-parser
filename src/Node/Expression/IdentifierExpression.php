<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Identifier;

/**
 * Name used as an expression: an enumerator, a variable or a macro the
 * preprocessor has left as is.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.2 Primary expressions
 */
final class IdentifierExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name the expression refers to
         */
        public Identifier $name,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
