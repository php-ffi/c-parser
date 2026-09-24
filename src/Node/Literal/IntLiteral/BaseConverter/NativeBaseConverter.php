<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\BaseConverter;

/**
 * Converts the base without any extension.
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
    private const LIMB_DIGITS = \PHP_INT_SIZE >= 8 ? 17 : 7;

    /**
     * The value a limb wraps at, the limbs are digits of a number in this
     * base.
     *
     * @var int<1, max>
     */
    private const LIMB_BASE = 10 ** self::LIMB_DIGITS;

    public function isAvailable(): bool
    {
        return true;
    }

    public function toDecimal(string $digits, int $base): string
    {
        $limbs = [0];

        for ($i = 0, $length = \strlen($digits); $i < $length; ++$i) {
            $carry = \intval($digits[$i], $base);

            for ($j = 0, $count = \count($limbs); $j < $count; ++$j) {
                $value = $limbs[$j] * $base + $carry;

                $limbs[$j] = $value % self::LIMB_BASE;
                $carry = \intdiv($value, self::LIMB_BASE);
            }

            if ($carry > 0) {
                $limbs[] = $carry;
            }
        }

        $last = \count($limbs) - 1;
        $result = (string) $limbs[$last];

        for ($i = $last - 1; $i >= 0; --$i) {
            $result .= \str_pad((string) $limbs[$i], self::LIMB_DIGITS, '0', \STR_PAD_LEFT);
        }

        \assert(\is_numeric($result));

        return $result;
    }
}
