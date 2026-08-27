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
}
