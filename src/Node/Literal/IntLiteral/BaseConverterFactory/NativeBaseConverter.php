<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverterInterface;

/**
 * Converts the base without any extension.
 *
 * The number is accumulated by the Horner scheme in decimal limbs, the least
 * significant first. Adding a digit only multiplies the number by the base
 * and adds the digit, so no arithmetic on two long numbers is needed.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class NativeBaseConverter implements BaseConverterInterface
{
    /**
     * Decimal digits in a limb. A limb multiplied by the largest base (36)
     * plus the largest digit (35) still fits in the native integer.
     *
     * @var int<1, max>
     */
    private const LIMB_DIGITS = \PHP_INT_SIZE >= 8 ? 9 : 4;

    /**
     * @var int<1, max>
     */
    private const LIMB_BASE = 10 ** self::LIMB_DIGITS;

    public function isAvailable(): bool
    {
        return true;
    }

    public function toDecimal(string $digits, int $base): string
    {
        $digits = \ltrim($digits, '0');

        if (\strlen($digits) <= self::getMaxSafeDigits($base)) {
            return (string) \intval($digits, $base);
        }

        $limbs = [0];

        foreach (\str_split($digits) as $digit) {
            $carry = \intval($digit, 36);

            foreach ($limbs as $i => $limb) {
                $value = $limb * $base + $carry;

                $limbs[$i] = $value % self::LIMB_BASE;
                $carry = \intdiv($value, self::LIMB_BASE);
            }

            if ($carry > 0) {
                $limbs[] = $carry;
            }
        }

        $result = (string) \array_pop($limbs);

        foreach (\array_reverse($limbs) as $limb) {
            $result .= \str_pad((string) $limb, self::LIMB_DIGITS, '0', \STR_PAD_LEFT);
        }

        \assert(\is_numeric($result));

        return $result;
    }

    /**
     * Returns the number of digits in the given base that always fit in a
     * PHP integer.
     *
     * @param int<2, 36> $base
     * @return int<0, max>
     */
    private static function getMaxSafeDigits(int $base): int
    {
        return \max(0, (int) \floor(\log(\PHP_INT_MAX, $base)));
    }
}
