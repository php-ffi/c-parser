<?php

declare(strict_types=1);

namespace FFI\Parser;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Exception\ValidationException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
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
 * Parses preprocessed C23 headers into the syntax tree.
 *
 * The parser checks the syntax. Whatever is syntactically valid gives a
 * syntax tree, even if a compiler rejects it, unless one of the given
 * validators rejects it.
 */
final class Parser
{
    private readonly ParserExecutor $parser;

    private readonly SourceFactoryInterface $sources;

    private readonly Transformer $transformer;

    private readonly Validator $validator;

    /**
     * @param Preset|null $preset what the compiler knows before the header,
     *        the default one is {@see Preset::default()}, which accepts every
     *        extension and knows no typedef names
     * @param iterable<mixed, ValidatorRuleInterface<covariant NodeInterface>>|null $rules
     *        checks of the syntax tree run by {@see parse()} without the ones
     *        the features of the preset remove, the default ones are
     *        {@see Validator::getDefaultRules()}
     * @param iterable<mixed, TransformerRuleInterface<covariant NodeInterface>>|null $transformers
     *        rewrites of the syntax tree run by {@see parse()} before the
     *        checks, the default ones are
     *        {@see Transformer::getDefaultTransformers()}
     * @param SourceFactoryInterface|null $sources creates the sources out of
     *        the values given to {@see parse()} and {@see validate()}, the
     *        default one accepts strings, files and streams
     */
    public function __construct(
        ?Preset $preset = null,
        ?iterable $rules = null,
        ?iterable $transformers = null,
        ?SourceFactoryInterface $sources = null,
    ) {
        $preset ??= Preset::default();

        $this->parser = self::createParser($preset);
        $this->transformer = self::createTransformer($preset, $transformers);
        $this->validator = self::createValidator($preset, $rules);
        $this->sources = self::createSourceFactory($sources);
    }

    /**
     * @param iterable<mixed, TransformerRuleInterface<covariant NodeInterface>>|null $transformers
     */
    private static function createTransformer(Preset $preset, ?iterable $transformers): Transformer
    {
        return new Transformer(
            rules: $transformers ?? Transformer::getDefaultTransformers(),
            typedefNames: $preset->typedefNames,
        );
    }

    /**
     * @param iterable<mixed, ValidatorRuleInterface<covariant NodeInterface>>|null $rules
     */
    private static function createValidator(Preset $preset, ?iterable $rules): Validator
    {
        $result = [];

        foreach ($rules ?? Validator::getDefaultRules() as $rule) {
            if (!$preset->isRuleRemoved($rule)) {
                $result[] = $rule;
            }
        }

        return new Validator($result);
    }

    private static function createSourceFactory(?SourceFactoryInterface $sources): SourceFactoryInterface
    {
        if ($sources === null) {
            return SourceFactory::createDefault();
        }

        return $sources;
    }

    private static function createParser(Preset $preset): ParserExecutor
    {
        return new ParserExecutor($preset->features);
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
     * @throws SyntaxErrorException in case of the header is not syntactically
     *         valid or is written in an extension of C23 the preset does not
     *         accept
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
     * the syntax tree. The validators are not run, and the syntax of every
     * extension of C23 the grammar knows is accepted, whichever features
     * the preset has.
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
