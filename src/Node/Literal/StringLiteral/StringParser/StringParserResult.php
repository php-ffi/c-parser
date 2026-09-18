<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\StringLiteral\StringParser;

use FFI\Parser\Node\Literal\EncodingPrefix;

/**
 * Parts of a character constant or a string literal parsed out of its
 * source text.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class StringParserResult
{
    public function __construct(
        /**
         * The characters between the quotes with the escape sequences
         * expanded
         */
        public string $value,
        /**
         * The encoding prefix or {@see null} in case of there is none
         */
        public ?EncodingPrefix $prefix,
    ) {}
}
