<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

/**
 * Occurs when the parser fails for a reason that is not a defect of the
 * parsed code, so there is no place in the source to point to.
 */
final class InternalException extends ParserException
{
    /**
     * @param \Throwable $reason the failure reported by the source
     */
    public static function becauseSourceIsNotReadable(\Throwable $reason): self
    {
        $message = \sprintf('The source cannot be read: %s', $reason->getMessage());

        return new self($message, 0, $reason);
    }
}
