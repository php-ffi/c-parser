<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\IntParser;

use FFI\Parser\Node\Literal\IntLiteral\IntBase;
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
        public readonly int $value,
        /**
         * @var numeric-string
         */
        public readonly string $decimal,
        public readonly IntBase $base,
        public readonly ?IntTypeSuffix $suffix,
    ) {}
}
