<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral\FloatParser;

use FFI\Parser\Node\Literal\FloatLiteral\FloatBase;
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
        public readonly float $value,
        /**
         * The digits with the point as written, without digit separators
         *
         * @var non-empty-string
         */
        public readonly string $mantissa,
        /**
         * The exponent as written, `0` in case of there is none
         *
         * @var numeric-string
         */
        public readonly string $exponent,
        /**
         * The base the mantissa is written in
         */
        public readonly FloatBase $base,
        /**
         * The type suffix or {@see null} in case of there is none
         */
        public readonly ?FloatTypeSuffix $suffix,
    ) {}
}
