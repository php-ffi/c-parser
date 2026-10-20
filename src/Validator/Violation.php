<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

/**
 * Breach of a rule in a fragment of the source.
 */
final class Violation
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $message,
        /**
         * @var int<0, max>
         */
        public readonly int $offset,
        /**
         * Size of the fragment in bytes, {@see null} when unknown
         *
         * @var int<0, max>|null
         */
        public readonly ?int $length = null,
    ) {}

    /**
     * Comparator of violations by offset.
     *
     * @return -1|0|1
     */
    public static function sort(Violation $a, Violation $b): int
    {
        return $a->offset <=> $b->offset;
    }
}
