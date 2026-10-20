<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\IntLiteral\IntParser;
use FFI\Parser\Node\Literal\IntLiteral\IntTypeSuffix;

/**
 * Integer constant, such as `42`, `0x1F'FFu` or `0b1010ull`. The base is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
abstract class IntLiteral extends Literal
{
    /**
     * The exact value in decimal, whatever its size.
     *
     * @var numeric-string
     */
    public readonly string $decimal;

    /**
     * @param numeric-string|null $decimal {@see null} to take {@see $value}
     * @param non-empty-string|null $raw {@see null} to write {@see $value} in the base of the constant
     * @param int<0, max> $offset
     */
    final public function __construct(
        /**
         * {@see \PHP_INT_MAX} for a constant that does not fit
         *
         * @var int<0, max>
         */
        public readonly int $value,
        ?string $decimal = null,
        /**
         * {@see null} for no suffix
         */
        public readonly ?IntTypeSuffix $suffix = null,
        /**
         * GCC: whether the constant is imaginary (`3j`)
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
     * Writes {@see $value} in the base of the constant with its prefix.
     *
     * @return non-empty-string
     */
    abstract protected function representAsRawNumber(): string;

    /**
     * @param non-empty-string $raw text of an integer constant in the base of the class
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
