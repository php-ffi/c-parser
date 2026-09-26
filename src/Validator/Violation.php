<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

/**
 * Breach of a rule found by a validator in a fragment of the source.
 */
final class Violation
{
    public function __construct(
        /**
         * Description of the breach
         *
         * @var non-empty-string
         */
        public readonly string $message,
        /**
         * Offset of the first byte of the fragment in the source, usually the
         * offset of the node the breach is found in
         *
         * @var int<0, max>
         */
        public readonly int $offset,
        /**
         * Size of the fragment in bytes or {@see null} in case of it is not
         * known
         *
         * @var int<0, max>|null
         */
        public readonly ?int $length = null,
    ) {}

    /**
     * Helper method for violation sorting.
     *
     * ```
     * \usort($visitor->violations, Violation::sort(...));
     * ```
     *
     * @return -1|0|1
     */
    public static function sort(Violation $a, Violation $b): int
    {
        return $a->offset <=> $b->offset;
    }
}
