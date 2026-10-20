<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\BaseConverter;

/**
 * Converts the digits of a binary, octal or hexadecimal integer constant
 * into the exact decimal string, however large the number is.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
interface BaseConverterInterface
{
    /**
     * Whether the extension the converter relies on is loaded.
     */
    public function isAvailable(): bool;

    /**
     * @param non-empty-string $digits `0-9`, then `a-z` in either case, each less than the base
     * @param int<2, 36> $base
     * @return numeric-string the decimal number without leading zeros
     */
    public function toDecimal(string $digits, int $base): string;
}
