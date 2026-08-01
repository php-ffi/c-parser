<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverterInterface;

/**
 * Converts the base by the `ext-gmp` extension.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Node\Literal
 */
final class GmpBaseConverter implements BaseConverterInterface
{
    public function isAvailable(): bool
    {
        return \extension_loaded('gmp');
    }

    public function toDecimal(string $digits, int $base): string
    {
        /** @var numeric-string */
        return \gmp_strval(\gmp_init($digits, $base));
    }
}
