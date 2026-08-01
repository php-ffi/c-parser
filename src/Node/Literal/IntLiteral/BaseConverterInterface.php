<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

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
     * Whether the converter can work in the current environment, i.e. the
     * extension or the package it relies on is available.
     */
    public function isAvailable(): bool;

    /**
     * @param non-empty-string $digits digits of the number (`0-9`, then
     *        `a-z` in either case), each of them less than the base
     * @param int<2, 36> $base
     * @return numeric-string the decimal number without leading zeros
     */
    public function toDecimal(string $digits, int $base): string;
}
