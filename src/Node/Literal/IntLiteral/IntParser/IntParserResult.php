<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\IntParser;

use FFI\Parser\Node\Literal\IntLiteral\IntTypeSuffix;

/**
 * Parts of an integer constant parsed out of its source text.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class IntParserResult
{
    public function __construct(
        /**
         * The value, saturated at {@see \PHP_INT_MAX}
         *
         * @var int<0, max>
         */
        public int $value,
        /**
         * The exact value in decimal digits
         *
         * @var numeric-string
         */
        public string $decimal,
        /**
         * The type suffix or {@see null} in case of there is none
         */
        public ?IntTypeSuffix $suffix,
        /**
         * Whether the imaginary suffix of GCC is written
         */
        public bool $isImaginary,
    ) {}
}
