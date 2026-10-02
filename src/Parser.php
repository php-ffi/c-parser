<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Exception\ValidationException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Parser\ParserExecutor;
use FFI\Parser\Transformer\TransformerRuleInterface;
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

    private readonly Transformer $transformer;

    private readonly Validator $validator;

    /**
     * @param SourceFactoryInterface|null $sources creates the sources out of
     *        the values given to {@see parse()} and {@see validate()}, the
     *        default one accepts strings, files and streams
     * @param iterable<mixed, ValidatorRuleInterface>|null $rules checks of the
     *        syntax tree run by {@see parse()}
     * @param iterable<mixed, TransformerRuleInterface>|null $transformers
     *        rewrites of the syntax tree run by {@see parse()} before the
     *        checks
     */
    public function __construct(
        ?SourceFactoryInterface $sources = null,
        ?iterable $rules = null,
        ?iterable $transformers = null,
    ) {
        $this->parser = self::createParser();
        $this->sources = self::createSourceFactory($sources);
        $this->transformer = self::createTransformer($transformers);
        $this->validator = self::createValidator($rules);
    }

    /**
     * @param iterable<mixed, TransformerRuleInterface>|null $rules
     */
    private static function createTransformer(?iterable $rules): Transformer
    {
        if ($rules === null) {
            return Transformer::createDefault();
        }

        return new Transformer($rules);
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
     * Parses the header into the syntax tree, runs the transformers over it
     * and then the validators over the resulting tree.
     *
     * @param mixed $source code of the header as a string, a file as an
     *        {@see \SplFileInfo}, a stream resource or a phplrt source
     * @return list<RootDeclarationInterface> declarations in the order they are
     *         written, one for each declarator, empty declarations (a lone `;`)
     *         are not listed
     * @throws InternalException in case of the header cannot be read or the
     *         lexer fails on it
     * @throws SyntaxErrorException in case of the header is not syntactically valid
     * @throws TraverserException in case of a transformer breaks the syntax
     *         tree
     * @throws ValidationException in case of the syntax tree breaks a rule of
     *         a validator
     */
    public function parse(mixed $source): array
    {
        $source = $this->createSource($source);

        $declarations = $this->parseOrFail($source);
        $declarations = $this->transformer->transform($declarations);

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
     * @throws TraverserException in case of a visitor breaks the syntax
     *         tree
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
