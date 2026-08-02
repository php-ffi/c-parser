<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory\BcMathBaseConverter;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory\GmpBaseConverter;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory\NativeBaseConverter;

/**
 * Selects the base converter for the current environment.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class BaseConverterFactory
{
    private static ?BaseConverterInterface $default = null;

    /**
     * Returns the fastest converter available in the current environment.
     * The choice is made once.
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
     * Returns the first of the given converters that is available, or the
     * extension-free {@see NativeBaseConverter} if none of them is.
     *
     * @param iterable<mixed, BaseConverterInterface> $converters in order of
     *        preference
     */
    private static function selectFirstAvailable(iterable $converters): BaseConverterInterface
    {
        foreach ($converters as $converter) {
            if ($converter->isAvailable()) {
                return $converter;
            }
        }

        return new NativeBaseConverter();
    }
}
