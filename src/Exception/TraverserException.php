<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

/**
 * Base of the failures caused by a visitor that breaks the syntax tree.
 */
abstract class TraverserException extends ParserException
{
    protected function __construct(string $message, ?\Throwable $reason = null)
    {
        parent::__construct($message, 0, $reason);
    }
}
