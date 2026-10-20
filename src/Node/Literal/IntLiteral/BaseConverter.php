<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\BaseConverterInterface;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\BcMathBaseConverter;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\GmpBaseConverter;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\NativeBaseConverter;

/**
 * Selects the base converter for the current environment.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class BaseConverter
{
    private static BaseConverterInterface $default;

    /**
     * Returns the fastest converter available in the current environment.
     */
    public static function createDefault(): BaseConverterInterface
    {
        return self::$default ??= self::selectFirstAvailable([
            new GmpBaseConverter(),
            // BCMath is slower than native php converter o_0
            // TBD remove it? lol
            new BcMathBaseConverter(),
        ]);
    }

    /**
     * @param non-empty-string $digits
     * @param int<2, 36> $base
     * @return numeric-string
     */
    public static function toDecimal(string $digits, int $base): string
    {
        $converter = self::createDefault();

        return $converter->toDecimal($digits, $base);
    }

    /**
     * Returns the first available converter or {@see NativeBaseConverter}.
     *
     * @param list<BaseConverterInterface> $converters in order of preference
     */
    private static function selectFirstAvailable(array $converters): BaseConverterInterface
    {
        foreach ($converters as $converter) {
            if ($converter->isAvailable()) {
                return $converter;
            }
        }

        return new NativeBaseConverter();
    }
}
