<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverterInterface;

/**
 * Converts the base by the `ext-bcmath` extension, which has no base
 * conversion of its own, so the digits are accumulated by the Horner scheme.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class BcMathBaseConverter implements BaseConverterInterface
{
    public function isAvailable(): bool
    {
        return \extension_loaded('bcmath');
    }

    public function toDecimal(string $digits, int $base): string
    {
        $result = '0';
        $multiplier = (string) $base;

        foreach (\str_split($digits) as $digit) {
            // The scale is explicit, so that a custom "bcmath.scale" setting
            // does not turn the result into a fractional number.
            $result = \bcadd(\bcmul($result, $multiplier, 0), (string) \intval($digit, $base), 0);
        }

        return $result;
    }
}
