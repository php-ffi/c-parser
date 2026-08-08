<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

/**
 * Predefined constant `nullptr`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4.6 Predefined constants
 */
final class NullptrLiteral extends Literal
{
    /**
     * @param non-empty-string|null $raw the literal exactly as written, {@see null}
     *        to spell it out of the value
     * @param int<0, max> $offset
     */
    public function __construct(?string $raw = null, int $offset = 0)
    {
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
        return 'nullptr';
    }
}
