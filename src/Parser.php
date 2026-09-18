<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Exception\ValidationException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Parser\ParserExecutor;
use FFI\Parser\Validator\ValidatorRuleInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserSourceException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\SourceFactory;

/**
 * Parses preprocessed C23 headers, with the extensions of the compilers,
 * into the syntax tree.
 *
 * The parser checks the syntax. Whatever is syntactically valid gives a
 * syntax tree, even if a compiler would reject it, unless one of the given
 * validators rejects it.
 */
final class Parser
{
    private readonly ParserExecutor $parser;

    private readonly SourceFactoryInterface $sources;

    private readonly Validator $validator;

    /**
     * @param SourceFactoryInterface|null $sources creates the sources out of
     *        the values given to {@see parse()} and {@see validate()}, the
     *        default one accepts strings, files and streams
     * @param iterable<mixed, ValidatorRuleInterface>|null $rules checks of the
     *        syntax tree run by {@see parse()}
     */
    public function __construct(
        ?SourceFactoryInterface $sources = null,
        ?iterable $rules = null,
    ) {
        $this->parser = self::createParser();
        $this->sources = self::createSourceFactory($sources);
        $this->validator = self::createValidator($rules);
    }

    /**
     * @param iterable<mixed, ValidatorRuleInterface>|null $rules
     */
    private static function createValidator(?iterable $rules): Validator
    {
        if ($rules === null) {
            return Validator::createDefault();
        }

        return new Validator($rules);
    }

    private static function createSourceFactory(?SourceFactoryInterface $sources): SourceFactoryInterface
    {
        if ($sources === null) {
            return SourceFactory::createDefault();
        }

        return $sources;
    }

    private static function createParser(): ParserExecutor
    {
        return new ParserExecutor();
    }

    /**
     * Parses the header into the syntax tree and runs the validators over
     * it.
     *
     * @param mixed $source code of the header as a string, a file as an
     *        {@see \SplFileInfo}, a stream resource or a phplrt source
     * @return list<RootDeclarationInterface> declarations in the order they are
     *         written, one for each declarator, empty declarations (a lone `;`)
     *         are not listed
     * @throws InternalException in case of the header cannot be read or the
     *         lexer fails on it
     * @throws SyntaxErrorException in case of the header is not syntactically valid
     * @throws ValidationException in case of the syntax tree breaks a rule of
     *         a validator
     */
    public function parse(mixed $source): array
    {
        $source = $this->createSource($source);

        $declarations = $this->parseOrFail($source);

        $this->validateOrFail($declarations, $source);

        return $declarations;
    }

    /**
     * Checks whether the header is syntactically valid, without building
     * the syntax tree. The validators are not run.
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
        } catch (ParserSourceException $e) {
            throw InternalException::becauseSourceIsNotReadable($e->getPrevious() ?? $e);
        } catch (ParserException $e) {
            throw InternalException::becauseSourceCannotBeAnalyzed($e->getPrevious() ?? $e);
        }
    }

    /**
     * @return list<RootDeclarationInterface>
     * @throws InternalException in case of the header cannot be read or the
     *         lexer fails on it
     * @throws SyntaxErrorException in case of the header is not syntactically valid
     */
    private function parseOrFail(ReadableInterface $source): array
    {
        try {
            return $this->parser->parse($source);
        } catch (UnexpectedTokenException $e) {
            throw SyntaxErrorException::becauseUnexpectedToken($e->source, $e->token, $e->getMessage());
        } catch (ParserSourceException $e) {
            throw InternalException::becauseSourceIsNotReadable($e->getPrevious() ?? $e);
        } catch (ParserException $e) {
            throw InternalException::becauseSourceCannotBeAnalyzed($e->getPrevious() ?? $e);
        }
    }

    /**
     * @param list<RootDeclarationInterface> $declarations
     * @throws ValidationException in case of the syntax tree breaks a rule of
     *         a validator
     */
    private function validateOrFail(array $declarations, ReadableInterface $source): void
    {
        $violations = $this->validator->validate($declarations);

        if ($violations !== []) {
            throw ValidationException::becauseValidationFailed($source, $violations);
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
