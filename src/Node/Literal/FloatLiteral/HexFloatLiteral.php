<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

use FFI\Parser\Node\Literal\FloatLiteral;

/**
 * Floating constant written in hexadecimal, after `0x` or `0X`, such as
 * `0x1.8p3`: the mantissa in hexadecimal digits and the exponent of 2 in
 * decimal ones.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 */
final class HexFloatLiteral extends FloatLiteral
{
    /**
     * The bound of the significand: doubles from this one up are even whole
     * numbers, a whole double below it fits in a native integer
     */
    private const MAX_SIGNIFICAND = 2 ** 53;

    /**
     * Scales the value by powers of two to a whole number, the mantissa
     * written without a point.
     */
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
