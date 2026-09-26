<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\IntLiteral\IntParser;
use FFI\Parser\Node\Literal\IntLiteral\IntTypeSuffix;

/**
 * Integer constant, such as `42`, `0x1F'FFu` or `0b1010ull`. The base it is
 * written in is the class of the node. It matters beyond the value: a
 * decimal constant without a suffix never gets an unsigned type, while the
 * other ones may.
 *
 * There is no sign: a minus before a constant is a unary operator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
abstract class IntLiteral extends Literal
{
    /**
     * The exact value of the constant as a decimal number, whatever its
     * size.
     *
     * @var numeric-string
     */
    public readonly string $decimal;

    /**
     * @param numeric-string|null $decimal the exact value in case of it does
     *        not fit in {@see $value}, {@see null} to take {@see $value}
     * @param non-empty-string|null $raw the literal exactly as written,
     *        {@see null} to write {@see $value} in the base of the constant
     * @param int<0, max> $offset
     */
    final public function __construct(
        /**
         * The value of the constant as a PHP integer, which is
         * {@see \PHP_INT_MAX} for a constant that does not fit in it (such
         * as `0xFFFFFFFFFFFFFFFF`)
         *
         * @var int<0, max>
         */
        public readonly int $value,
        ?string $decimal = null,
        /**
         * {@see null} in case of there is no suffix, i.e. the constant gets
         * the first type it fits in, starting from `int`
         */
        public readonly ?IntTypeSuffix $suffix = null,
        /**
         * GCC: whether the constant is imaginary, written with the suffix
         * `i` or `j` (`2.0i`, `3j`), its type is then the complex type of
         * the constant
         */
        public readonly bool $isImaginary = false,
        ?string $raw = null,
        int $offset = 0,
    ) {
        $this->decimal = $decimal ?? (string) $value;

        parent::__construct(
            raw: $raw ?? $this->representAsRawValue(),
            offset: $offset,
        );
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
     * Writes {@see $value} in the base of the constant, with its prefix.
     *
     * @return non-empty-string
     */
    abstract protected function representAsRawNumber(): string;

    /**
     * Builds the constant out of its source text.
     *
     * @param non-empty-string $raw text of an integer constant written in
     *        the base of the class
     * @param int<0, max> $offset
     */
    public static function parse(string $raw, int $offset = 0): static
    {
        $result = IntParser::parse($raw);

        // @phpstan-ignore-next-line the grammar only calls it on the concrete classes, picked by the token
        return new static(
            value: $result->value,
            decimal: $result->decimal,
            suffix: $result->suffix,
            isImaginary: $result->isImaginary,
            raw: $raw,
            offset: $offset,
        );
    }
}
