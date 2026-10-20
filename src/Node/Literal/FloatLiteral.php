<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\FloatLiteral\FloatParser;
use FFI\Parser\Node\Literal\FloatLiteral\FloatTypeSuffix;

/**
 * Floating constant, such as `1.5`, `.5e-3f` or `0x1.8p3`. The base is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H.5.2 Constants
 */
abstract class FloatLiteral extends Literal
{
    /**
     * The digits with the point and without digit separators: `1.5` in `1.5e3`,
     * `1.8` in `0x1.8p3`. Together with {@see $exponent} keeps the exact value.
     *
     * @var non-empty-string
     */
    public readonly string $mantissa;

    /**
     * The power of 10 (decimal) or 2 (hexadecimal): `3` in `1.5e3`, `0` if none.
     *
     * @var numeric-string
     */
    public readonly string $exponent;

    /**
     * @param non-empty-string|null $mantissa {@see null} to take it and the exponent out of {@see $value}
     * @param numeric-string|null $exponent {@see null} for none
     * @param non-empty-string|null $raw {@see null} to write it out of the mantissa and the exponent
     * @param int<0, max> $offset
     */
    final public function __construct(
        /**
         * {@see \INF} for a constant too large for a double, zero for one too small,
         * an approximation for one more precise than a double (`0.1dd`)
         */
        public readonly float $value,
        ?string $mantissa = null,
        ?string $exponent = null,
        /**
         * {@see null} for no suffix, the constant is a `double` then
         */
        public readonly ?FloatTypeSuffix $suffix = null,
        /**
         * The width in the suffix: `16` in `1.0f16`, {@see null} for a suffix without one
         *
         * @var int<1, max>|null
         */
        public readonly ?int $suffixWidth = null,
        /**
         * GCC: whether the constant is imaginary (`2.0i`)
         */
        public readonly bool $isImaginary = false,
        ?string $raw = null,
        int $offset = 0,
    ) {
        [$this->mantissa, $this->exponent]
            = $this->representAsFloatParts($mantissa, $exponent);

        parent::__construct(
            raw: $raw ?? $this->representAsRawValue(),
            offset: $offset,
        );
    }

    /**
     * @param non-empty-string|null $mantissa
     * @param numeric-string|null $exponent
     * @return array{non-empty-string, numeric-string}
     */
    private function representAsFloatParts(?string $mantissa, ?string $exponent): array
    {
        if ($mantissa === null) {
            return $this->splitValue();
        }

        return [$mantissa, $exponent ?? '0'];
    }

    /**
     * @return non-empty-string
     */
    private function representAsRawValue(): string
    {
        return $this->representAsRawNumber()
            . $this->suffix?->getSpelling($this->suffixWidth)
            . ($this->isImaginary ? 'i' : '');
    }

    /**
     * Writes the finite {@see $value} as the mantissa and the exponent.
     *
     * @return array{non-empty-string, numeric-string}
     */
    abstract protected function splitValue(): array;

    /**
     * Writes the mantissa and the exponent as a floating constant of the base.
     *
     * @return non-empty-string
     */
    abstract protected function representAsRawNumber(): string;

    /**
     * @param non-empty-string $raw text of a floating constant in the base of the class
     * @param int<0, max> $offset
     */
    public static function parse(string $raw, int $offset = 0): static
    {
        $result = FloatParser::parse($raw);

        // @phpstan-ignore-next-line the grammar only calls it on the concrete classes, picked by the token
        return new static(
            value: $result->value,
            mantissa: $result->mantissa,
            exponent: $result->exponent,
            suffix: $result->suffix,
            suffixWidth: $result->suffixWidth,
            isImaginary: $result->isImaginary,
            raw: $raw,
            offset: $offset,
        );
    }
}
