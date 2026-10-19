<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when the source is not a syntactically valid header, whether a
 * fragment of it is not a lexeme of C at all or the lexemes are misplaced.
 */
final class SyntaxErrorException extends ParseRuntimeException
{
    /**
     * @param string $message description of the failure, either the one
     *        given by the grammar or the list of expected lexemes
     */
    public static function becauseUnexpectedToken(
        ReadableInterface $source,
        TokenInterface $token,
        string $message,
    ): self {
        return new self($message, $source, $token->offset, $token->size);
    }

    /**
     * @param non-empty-string $extension name of the extension of C23 the
     *        fragment is written in
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
