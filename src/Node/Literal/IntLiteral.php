<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\IntLiteral\IntBase;
use FFI\Parser\Node\Literal\IntLiteral\IntParser;
use FFI\Parser\Node\Literal\IntLiteral\IntTypeSuffix;

/**
 * Integer constant, such as `42`, `0x1F'FFu` or `0b1010ull`.
 *
 * There is no sign: a minus before a constant is a unary operator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.2 Integer constants
 */
final class IntLiteral extends Literal
{
    /**
     * @param non-empty-string|null $raw the constant exactly as written
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The value of the constant as a PHP integer, which is
         * {@see \PHP_INT_MAX} for a constant that does not fit in it (such
         * as `0xFFFFFFFFFFFFFFFF`)
         */
        public readonly int $value,
        /**
         * The exact value of the constant as a decimal number, whatever
         * its size
         *
         * @var numeric-string
         */
        public readonly string $decimal,
        /**
         * The base the constant is written in
         */
        public readonly IntBase $base = IntBase::DEFAULT,
        /**
         * {@see null} in case of there is no suffix, i.e. the constant gets
         * the first type it fits in, starting from `int`
         */
        public readonly ?IntTypeSuffix $suffix = null,
        ?string $raw = null,
        int $offset = 0,
    ) {
        parent::__construct(
            raw: $raw ?? match ($this->base) {
                IntBase::Decimal => $this->decimal,
                IntBase::Hexadecimal => '0x' . \dechex($this->value),
                IntBase::Octal => '0' . \decoct($this->value),
                IntBase::Binary => '0b' . \decbin($this->value),
            } . $this->suffix?->value,
            offset: $offset,
        );
    }

    /**
     * Builds the constant out of its source text.
     *
     * @param non-empty-string $raw text of a `T_INT_LITERAL` token
     * @param int<0, max> $offset
     */
    public static function parse(string $raw, int $offset = 0): self
    {
        $result = IntParser::parse($raw);

        return new self(
            value: $result->value,
            decimal: $result->decimal,
            base: $result->base,
            suffix: $result->suffix,
            raw: $raw,
            offset: $offset,
        );
    }
}
