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
         * Saturated at {@see \PHP_INT_MAX}
         *
         * @var int<0, max>
         */
        public int $value,
        /**
         * @var numeric-string
         */
        public string $decimal,
        public ?IntTypeSuffix $suffix,
        public bool $isImaginary,
    ) {}
}
