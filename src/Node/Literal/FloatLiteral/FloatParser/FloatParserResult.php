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
         * {@see \INF} if too large for a double
         */
        public float $value,
        /**
         * @var non-empty-string
         */
        public string $mantissa,
        /**
         * `0` if none is written
         *
         * @var numeric-string
         */
        public string $exponent,
        public ?FloatTypeSuffix $suffix,
        /**
         * @var int<1, max>|null
         */
        public ?int $suffixWidth,
        public bool $isImaginary,
    ) {}
}
