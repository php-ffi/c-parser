<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\FloatLiteral;

/**
 * Suffix of a floating constant, named by the type it gives the constant.
 * Spellings of the same type (`df` and `d32`) are one case, the exact
 * spelling is kept by the raw text of the constant. The value of a case is
 * the spelling a constant built without the source text gets.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H, interchange and extended types
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
     * `f16` or `F16`.
     */
    case Float16 = 'f16';

    /**
     * `f32` or `F32`.
     */
    case Float32 = 'f32';

    /**
     * `f64` or `F64`.
     */
    case Float64 = 'f64';

    /**
     * `f128` or `F128`.
     */
    case Float128 = 'f128';

    /**
     * `f32x` or `F32x`.
     */
    case Float32x = 'f32x';

    /**
     * `f64x` or `F64x`.
     */
    case Float64x = 'f64x';

    /**
     * `f128x` or `F128x`.
     */
    case Float128x = 'f128x';

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
     * `d64x` or `D64x`.
     */
    case Decimal64x = 'd64x';
}
