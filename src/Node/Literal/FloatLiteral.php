<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\FloatLiteral\FloatParser;
use FFI\Parser\Node\Literal\FloatLiteral\FloatTypeSuffix;

/**
 * Floating constant, such as `1.5`, `.5e-3f` or `0x1.8p3`. The base it is
 * written in is the class of the node.
 *
 * There is no sign: a minus before a constant is a unary operator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.3 Floating constants
 */
abstract class FloatLiteral extends Literal
{
    /**
     * The digits of the constant with the point, if one is written, in its
     * base and without digit separators: `1.5` in `1.5e3`, `1` in `1e5`,
     * `1.8` in `0x1.8p3`. Along with {@see $exponent} it keeps the value
     * exactly, whatever its precision.
     *
     * @var non-empty-string
     */
    public readonly string $mantissa;

    /**
     * The exponent, a power of 10 for a decimal constant and of 2 for a
     * hexadecimal one: `3` in `1.5e3` and in `0x1.8p3`, `0` in case of there
     * is none.
     *
     * @var numeric-string
     */
    public readonly string $exponent;

    /**
     * @param non-empty-string|null $mantissa the exact digits, {@see null}
     *        to take them and the exponent out of {@see $value}
     * @param numeric-string|null $exponent the exact exponent of the given
     *        mantissa, {@see null} for none
     * @param non-empty-string|null $raw the literal exactly as written,
     *        {@see null} to write it out of the mantissa and the exponent
     * @param int<0, max> $offset
     */
    final public function __construct(
        /**
         * Value of the constant as a PHP float. It is {@see \INF} for a
         * constant too large for it (`1e999`), zero for one too small
         * (`1e-999`) and an approximation for a constant more precise than
         * a double (`0.1dd`, `1.1L`)
         */
        public readonly float $value,
        ?string $mantissa = null,
        ?string $exponent = null,
        /**
         * {@see null} in case of there is no suffix, i.e. the constant is a
         * `double`
         */
        public readonly ?FloatTypeSuffix $suffix = null,
        /**
         * GCC: whether the constant is imaginary, written with the suffix
         * `i` or `j` (`2.0i`, `3j`), its type is then the complex type of
         * the constant
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
            . $this->suffix?->value
            . ($this->isImaginary ? 'i' : '');
    }

    /**
     * Writes the finite {@see $value} as the mantissa and the exponent in
     * the base of the constant.
     *
     * @return array{non-empty-string, numeric-string}
     */
    abstract protected function splitValue(): array;

    /**
     * Writes the mantissa and the exponent with the prefix and the exponent
     * letter of the base, a text that reads back as a floating constant.
     *
     * @return non-empty-string
     */
    abstract protected function representAsRawNumber(): string;

    /**
     * Builds the constant out of its source text.
     *
     * @param non-empty-string $raw text of a floating constant written in
     *        the base of the class
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
            isImaginary: $result->isImaginary,
            raw: $raw,
            offset: $offset,
        );
    }
}
