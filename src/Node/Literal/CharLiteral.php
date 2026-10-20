<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\StringLiteral\StringParser;

/**
 * Character constant, such as `'a'`, `'\n'` or `u8'x'`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.5 Character constants
 */
final class CharLiteral extends Literal
{
    /**
     * @param non-empty-string|null $raw the literal exactly as written, {@see null}
     *        to spell it out of the value
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * Characters between the quotes with the escape sequences expanded
         * (UTF-8, an octal or hexadecimal escape of a narrow constant stays a byte,
         * a value that does not fit becomes U+FFFD). A multicharacter constant keeps all of them.
         */
        public readonly string $value,
        /**
         * {@see null} for no prefix, the constant is an `int` then
         */
        public readonly ?EncodingPrefix $prefix = null,
        ?string $raw = null,
        int $offset = 0,
    ) {
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
        return \sprintf("%s'%s'", $this->prefix?->value, \addcslashes($this->value, "\0..\37'\\\177"));
    }

    /**
     * @param non-empty-string $raw text of a `T_CHAR_LITERAL` token
     * @param int<0, max> $offset
     */
    public static function parse(string $raw, int $offset = 0): self
    {
        $result = StringParser::parse($raw);

        return new self(
            value: $result->value,
            prefix: $result->prefix,
            raw: $raw,
            offset: $offset,
        );
    }
}
