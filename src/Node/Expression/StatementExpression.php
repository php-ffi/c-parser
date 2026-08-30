<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

/**
 * GCC: a compound statement in parentheses whose value is the one of its
 * last statement, `({ int y = x; y * 2; })`. Its statements are not parsed:
 * the body is any sequence of tokens with balanced parentheses, brackets
 * and braces, and keeps its text.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Statement-Exprs.html GCC, Statements and Declarations in Expressions
 */
final class StatementExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The compound statement exactly as written, the braces included
         *
         * @var non-empty-string
         */
        public readonly string $body,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
