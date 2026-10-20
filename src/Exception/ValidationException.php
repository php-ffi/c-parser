<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use FFI\Parser\Validator\Violation;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a validator rejects the tree. Points to the first violation and holds all of them.
 */
final class ValidationException extends ParseRuntimeException
{
    private function __construct(
        string $message,
        ReadableInterface $source,
        /**
         * In the order of their offsets
         *
         * @var non-empty-list<Violation>
         */
        public readonly array $violations,
    ) {
        parent::__construct(
            message: $message,
            source: $source,
            offset: $violations[0]->offset,
            length: $violations[0]->length,
        );
    }

    /**
     * @param non-empty-list<Violation> $violations
     */
    public static function becauseValidationFailed(ReadableInterface $source, array $violations): self
    {
        $message = $violations[0]->message;

        if (\count($violations) > 1) {
            $message .= \sprintf(' (and %d more)', \count($violations) - 1);
        }

        return new self($message, $source, $violations);
    }
}
