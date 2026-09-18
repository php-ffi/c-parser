<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition;

use FFI\Parser\Node\Node;

/**
 * Body of a function definition, the compound statement in braces. Its
 * statements are not parsed: the body is any sequence of tokens with
 * balanced parentheses, brackets and braces, and keeps its text.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.8.3 Compound statement
 */
final class FunctionBody extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The body exactly as written, the braces included
         *
         * @var non-empty-string
         */
        public string $raw,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
