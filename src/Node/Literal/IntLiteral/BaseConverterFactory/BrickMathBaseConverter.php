<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory;

use Brick\Math\BigInteger;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverterInterface;

/**
 * Converts the base by the `brick/math` package. The package is not a
 * dependency of the library, so the converter is only used when the package
 * has been installed by the application.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class BrickMathBaseConverter implements BaseConverterInterface
{
    public function isAvailable(): bool
    {
        return \class_exists(BigInteger::class);
    }

    public function toDecimal(string $digits, int $base): string
    {
        $result = (string) BigInteger::fromBase($digits, $base);

        \assert(\is_numeric($result));

        return $result;
    }
}
