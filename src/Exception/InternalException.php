<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

/**
 * Occurs when the parser fails for a reason that is not a defect of the
 * parsed code, so there is no place in the source to point to.
 */
final class InternalException extends ParserException
{
    private function __construct(string $message, \Throwable $reason)
    {
        parent::__construct($message, 0, $reason);
    }

    /**
     * @param \Throwable $reason the failure reported by the source
     */
    public static function becauseSourceIsNotReadable(\Throwable $reason): self
    {
        $message = \sprintf('The source cannot be read: "%s"', \addcslashes($reason->getMessage(), '"'));

        return new self($message, $reason);
    }

    /**
     * @param \Throwable $reason the failure reported while the source was
     *        split into lexemes, such as a limit of PCRE
     */
    public static function becauseSourceCannotBeAnalyzed(\Throwable $reason): self
    {
        $message = \sprintf('The source cannot be analyzed: "%s"', \addcslashes($reason->getMessage(), '"'));

        return new self($message, $reason);
    }
}
