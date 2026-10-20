<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Expression\Expression;

/**
 * Base of the constants and string literals, which keep their source text.
 */
abstract class Literal extends Expression implements
    LiteralInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $raw,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
