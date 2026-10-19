<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

/**
 * Suffix of a floating constant, named by the type it gives the constant.
 * Spellings of the same type (`df` and `d32`) are one case, the exact
 * spelling is kept by the raw text of the constant. The value of a case is
 * the spelling a constant built without the source text gets, `N` stands
 * for the width of the type.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H.5.2 Constants
 * @link https://gcc.gnu.org/onlinedocs/gcc/Floating-Types.html GCC, additional floating types
 */
enum FloatTypeSuffix: string
{
    /**
     * `f` or `F`.
     */
    case Float = 'f';

    /**
     * `l` or `L`.
     */
    case LongDouble = 'l';

    /**
     * `fN` or `FN`, the `_FloatN` type: `f16`, `F128`.
     */
    case FloatN = 'fN';

    /**
     * `fNx` or `FNx`, the `_FloatNx` type: `f32x`, `F64x`.
     */
    case FloatNx = 'fNx';

    /**
     * `df`, `DF`, `d32` or `D32`.
     */
    case Decimal32 = 'df';

    /**
     * `dd`, `DD`, `d64` or `D64`.
     */
    case Decimal64 = 'dd';

    /**
     * `dl`, `DL`, `d128` or `D128`.
     */
    case Decimal128 = 'dl';

    /**
     * `dN` or `DN` besides `d32`, `d64` and `d128`, the `_DecimalN` type:
     * `d96`.
     */
    case DecimalN = 'dN';

    /**
     * `dNx` or `DNx`, the `_DecimalNx` type: `d64x`, `D128x`.
     */
    case DecimalNx = 'dNx';

    /**
     * GCC: `q` or `Q`, the `__float128` type.
     */
    case GnuFloat128 = 'q';

    /**
     * GCC: `w` or `W`, the `__float80` type (`__ibm128` on PowerPC).
     */
    case GnuFloat80 = 'w';

    /**
     * Whether the suffix is written with the width of its type.
     */
    public function hasWidth(): bool
    {
        return match ($this) {
            self::FloatN, self::FloatNx, self::DecimalN, self::DecimalNx => true,
            default => false,
        };
    }

    /**
     * Whether the suffix gives a decimal floating type.
     */
    public function isDecimal(): bool
    {
        return match ($this) {
            self::Decimal32, self::Decimal64, self::Decimal128, self::DecimalN, self::DecimalNx => true,
            default => false,
        };
    }

    /**
     * Returns the spelling of the suffix with the given width in place of
     * `N`.
     *
     * @param int<1, max>|null $width the width of the type, {@see null} for
     *        a suffix without one
     * @return non-empty-string
     */
    public function getSpelling(?int $width = null): string
    {
        /** @var non-empty-string */
        return \str_replace('N', (string) $width, $this->value);
    }
}
