<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

/**
 * Occurs when the parser fails for a reason other than a defect of the parsed code.
 */
final class InternalException extends ParserException
{
    private function __construct(string $message, \Throwable $reason)
    {
        parent::__construct($message, 0, $reason);
    }

    public static function becauseSourceIsNotReadable(\Throwable $reason): self
    {
        $message = \sprintf('The source cannot be read: "%s"', \addcslashes($reason->getMessage(), '"'));

        return new self($message, $reason);
    }

    public static function becauseSourceCannotBeAnalyzed(\Throwable $reason): self
    {
        $message = \sprintf('The source cannot be analyzed: "%s"', \addcslashes($reason->getMessage(), '"'));

        return new self($message, $reason);
    }
}
