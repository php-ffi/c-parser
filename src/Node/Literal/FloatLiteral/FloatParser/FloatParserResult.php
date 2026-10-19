<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral\FloatParser;

use FFI\Parser\Node\Literal\FloatLiteral\FloatTypeSuffix;

/**
 * Parts of a floating constant parsed out of its source text.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class FloatParserResult
{
    public function __construct(
        /**
         * The value rounded to a double, {@see \INF} in case of it is too
         * large for one
         */
        public float $value,
        /**
         * The digits with the point as written, without digit separators
         *
         * @var non-empty-string
         */
        public string $mantissa,
        /**
         * The exponent as written, `0` in case of there is none
         *
         * @var numeric-string
         */
        public string $exponent,
        /**
         * The type suffix or {@see null} in case of there is none
         */
        public ?FloatTypeSuffix $suffix,
        /**
         * The width written in the suffix, {@see null} in case of the
         * suffix has none
         *
         * @var int<1, max>|null
         */
        public ?int $suffixWidth,
        /**
         * Whether the imaginary suffix of GCC is written
         */
        public bool $isImaginary,
    ) {}
}
