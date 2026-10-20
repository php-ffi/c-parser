<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

use FFI\Parser\Node\Literal\FloatLiteral;

/**
 * Floating constant written in hexadecimal, such as `0x1.8p3`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 */
final class HexFloatLiteral extends FloatLiteral
{
    /**
     * Bound of the significand: doubles from this one up are even whole numbers.
     */
    private const MAX_SIGNIFICAND = 2 ** 53;

    protected function splitValue(): array
    {
        $mantissa = $this->value;
        $exponent = 0;

        while ($mantissa > \floor($mantissa)) {
            $mantissa *= 2;
            --$exponent;
        }

        while ($mantissa >= self::MAX_SIGNIFICAND && \is_finite($mantissa)) {
            $mantissa /= 2;
            ++$exponent;
        }

        /** @var array{non-empty-string, numeric-string} */
        return [\dechex((int) $mantissa), (string) $exponent];
    }

    protected function representAsRawNumber(): string
    {
        return '0x' . $this->mantissa . 'p' . $this->exponent;
    }
}
