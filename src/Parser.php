<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\SyntaxException;
use FFI\Parser\Node\Node;
use FFI\Parser\Parser\ParserExecutor;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\SourceFactory;

/**
 * Parses preprocessed C23 headers, the ones accepted by {@see \FFI::cdef()},
 * into the syntax tree.
 *
 * The parser only checks the syntax. Whatever is syntactically valid gives
 * a syntax tree, even if a compiler would reject it.
 */
final class Parser
{
    private readonly ParserExecutor $parser;

    private readonly SourceFactoryInterface $sources;

    /**
     * @param SourceFactoryInterface|null $sources creates the sources out of
     *        the values given to {@see parse()} and {@see validate()}, the
     *        default one accepts strings, files and streams
     */
    public function __construct(?SourceFactoryInterface $sources = null)
    {
        $this->parser = new ParserExecutor();
        $this->sources = $sources ?? SourceFactory::createDefault();
    }

    /**
     * Parses the header into the syntax tree.
     *
     * @param mixed $source code of the header as a string, a file as an
     *        {@see \SplFileInfo}, a stream resource or a phplrt source
     * @return list<Node> external declarations in the order they are
     *         written, empty declarations (a lone `;`) are not listed
     * @throws InternalException in case of the header cannot be read or the
     *         lexer fails on it
     * @throws SyntaxException in case of the header is not syntactically valid
     */
    public function parse(mixed $source): array
    {
        $source = $this->createSource($source);

        try {
            return $this->parser->parse($source);
        } catch (UnexpectedTokenException $e) {
            throw SyntaxException::becauseUnexpectedToken($e->source, $e->token, $e->getMessage());
            // @phpstan-ignore-next-line the lexer throws it, parse() of the generated parser does not declare it
        } catch (LexerRuntimeExceptionInterface $e) {
            throw InternalException::becauseSourceCannotBeAnalyzed($e);
            // @phpstan-ignore-next-line the lexer throws it, parse() of the generated parser does not declare it
        } catch (LexerExceptionInterface $e) {
            throw InternalException::becauseSourceIsNotReadable($e->getPrevious() ?? $e);
        }
    }

    /**
     * Checks whether the header is syntactically valid, without building
     * the syntax tree.
     *
     * @param mixed $source code of the header as a string, a file as an
     *        {@see \SplFileInfo}, a stream resource or a phplrt source
     * @throws InternalException in case of the header cannot be read or the
     *         lexer fails on it
     */
    public function validate(mixed $source): bool
    {
        $source = $this->createSource($source);

        try {
            return $this->parser->validate($source);
        } catch (LexerRuntimeExceptionInterface $e) {
            throw InternalException::becauseSourceCannotBeAnalyzed($e);
        } catch (LexerExceptionInterface $e) {
            throw InternalException::becauseSourceIsNotReadable($e->getPrevious() ?? $e);
        }
    }

    /**
     * @throws InternalException in case of no source can be created out of
     *         the given value
     */
    private function createSource(mixed $source): ReadableInterface
    {
        try {
            return $this->sources->create($source);
        } catch (SourceExceptionInterface $e) {
            throw InternalException::becauseSourceIsNotReadable($e);
        }
    }
}
