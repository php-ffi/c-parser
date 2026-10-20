<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when the source is not a syntactically valid header.
 */
final class SyntaxErrorException extends ParseRuntimeException
{
    public static function becauseUnexpectedToken(
        ReadableInterface $source,
        TokenInterface $token,
        string $message,
    ): self {
        return new self($message, $source, $token->offset, $token->size);
    }

    /**
     * @param non-empty-string $extension
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public static function becauseExtensionIsDisabled(
        ReadableInterface $source,
        string $extension,
        int $offset,
        int $length,
    ): self {
        $message = \sprintf('Syntax error, the extension "%s" is not enabled', \addcslashes($extension, '"'));

        return new self($message, $source, $offset, $length);
    }
}
