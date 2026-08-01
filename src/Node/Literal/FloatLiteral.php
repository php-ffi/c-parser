<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

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
     * @param non-empty-string|null $raw the value exactly as written
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * Value of the constant as a PHP float. It is {@see \INF} for a
         * constant that does not fit in it (`1e999`) and an approximation for
         * a decimal floating constant (`0.1dd`), the exact value is kept by
         * the raw text.
         */
        public readonly float $value,
        /**
         * {@see null} in case of there is no suffix, i.e. the constant is a
         * `double`
         */
        public readonly ?FloatTypeSuffix $suffix = null,
        ?string $raw = null,
        int $offset = 0,
    ) {
        parent::__construct(
            raw: $raw ?? self::formatValue($this->value) . $this->suffix?->value,
            offset: $offset,
        );
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
            suffix: $result->suffix,
            raw: $raw,
            offset: $offset,
        );
    }

    /**
     * Unlike a cast to string, keeps the fraction of a whole value (`1.0`),
     * so that it is not read as an integer constant.
     *
     * @return non-empty-string
     */
    private static function formatValue(float $value): string
    {
        /** @var non-empty-string */
        return \var_export($value, true);
    }
}
