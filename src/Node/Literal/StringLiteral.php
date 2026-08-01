<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Literal\StringLiteral\StringParser;
use FFI\Parser\Node\Literal\StringLiteral\StringTypePrefix;

/**
 * String literal, such as `"text"`, `L"wide"` or `u8"\u00E9"`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.5 String literals
 */
final class StringLiteral extends Literal
{
    /**
     * @param non-empty-string|null $raw the value exactly as written
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * Characters between the quotes with the escape sequences expanded,
         * see {@see StringParser}
         */
        public readonly string $value,
        /**
         * {@see null} in case of there is no prefix, i.e. the literal is an
         * array of `char`
         */
        public readonly ?StringTypePrefix $prefix = null,
        ?string $raw = null,
        int $offset = 0,
    ) {
        parent::__construct(
            raw: $raw ?? \sprintf('%s"%s"', $this->prefix?->value, \addcslashes($this->value, '"\\')),
            offset: $offset,
        );
    }

    /**
     * Builds the literal out of its source text.
     *
     * @param non-empty-string $raw text of a `T_STRING_LITERAL` token
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
