<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\BaseConverter;

/**
 * Converts the base by the `ext-bcmath` extension, accumulating the digits
 * by the Horner scheme.
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
            // Whole numbers regardless of the "bcmath.scale" setting
            $result = \bcadd(\bcmul($result, $multiplier, 0), (string) \intval($digit, $base), 0);
        }

        return $result;
    }
}
