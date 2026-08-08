<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\FloatLiteral\FloatBase;
use FFI\Parser\Node\Literal\FloatLiteral\FloatParser;
use FFI\Parser\Node\Literal\FloatLiteral\FloatTypeSuffix;

/**
 * Floating constant, such as `1.5`, `.5e-3f` or `0x1.8p3`.
 *
 * There is no sign: a minus before a constant is a unary operator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 */
final class FloatLiteral extends Literal
{
    /**
     * @param non-empty-string|null $raw the literal exactly as written, {@see null}
     *        to write it out of the mantissa and the exponent
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * Value of the constant as a PHP float. It is {@see \INF} for a
         * constant too large for it (`1e999`), zero for one too small
         * (`1e-999`) and an approximation for a constant more precise than
         * a double (`0.1dd`, `1.1L`), the exact value is kept by
         * {@see $mantissa} and {@see $exponent}.
         */
        public readonly float $value,
        /**
         * The digits with the point, in the base of the constant and without
         * digit separators: `1.5` in `1'000.5e3`, `1.8` in `0x1.8p3`
         *
         * @var non-empty-string
         */
        public readonly string $mantissa,
        /**
         * The exponent, a power of 10 for a decimal constant and of 2 for a
         * hexadecimal one: `3` in `1.5e3` and in `0x1.8p3`, `0` in case of
         * there is none
         *
         * @var numeric-string
         */
        public readonly string $exponent = '0',
        /**
         * The base the mantissa is written in
         */
        public readonly FloatBase $base = FloatBase::DEFAULT,
        /**
         * {@see null} in case of there is no suffix, i.e. the constant is a
         * `double`
         */
        public readonly ?FloatTypeSuffix $suffix = null,
        ?string $raw = null,
        int $offset = 0,
    ) {
        parent::__construct(
            raw: $raw ?? $this->representAsRawValue(),
            offset: $offset,
        );
    }

    /**
     * The exponent is always written, so that the text is not read as an
     * integer constant.
     *
     * @return non-empty-string
     */
    private function representAsRawValue(): string
    {
        return match ($this->base) {
            FloatBase::Decimal => $this->mantissa . 'e' . $this->exponent,
            FloatBase::Hexadecimal => '0x' . $this->mantissa . 'p' . $this->exponent,
        } . $this->suffix?->value;
    }

    /**
     * Builds the constant out of its source text.
     *
     * @param non-empty-string $raw text of a `T_FLOAT_LITERAL` token
     * @param int<0, max> $offset
     */
    public static function parse(string $raw, int $offset = 0): self
    {
        $result = FloatParser::parse($raw);

        return new self(
            value: $result->value,
            mantissa: $result->mantissa,
            exponent: $result->exponent,
            base: $result->base,
            suffix: $result->suffix,
            raw: $raw,
            offset: $offset,
        );
    }
}
