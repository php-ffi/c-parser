<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

/**
 * Floating constant written in decimal, such as `1.5` or `.5e-3f`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 */
final class DecFloatLiteral extends FloatLiteral
{
    protected function splitValue(): array
    {
        // The shortest text that reads back as the same double, with an
        // upper case "E" before the exponent if there is one
        $parts = \explode('E', \var_export($this->value, true), 2);

        /** @var array{non-empty-string, numeric-string} */
        return [$parts[0], $parts[1] ?? '0'];
    }

    protected function representAsRawNumber(): string
    {
        // Without a point and an exponent the text is an integer constant
        if ($this->exponent === '0' && \str_contains($this->mantissa, '.')) {
            return $this->mantissa;
        }

        return $this->mantissa . 'e' . $this->exponent;
    }
}
