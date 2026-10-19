<?php

declare(strict_types=1);

namespace FFI\Parser\Parser;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Preset\Feature;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Exception\LexicalErrorException;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserSourceException;

/**
 * The generated parser with the syntax check opened up and the extensions
 * of C23 its reducers accept.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 *
 * @template-extends CompiledParserExecutor<list<RootDeclarationInterface>>
 */
final class ParserExecutor extends CompiledParserExecutor
{
    public function __construct(
        /**
         * Extensions of C23 the reducers accept
         *
         * @var list<Feature>
         */
        private readonly array $features = [],
    ) {
        parent::__construct();
    }

    /**
     * Rejects the fragment of an extension of C23 unless the extension is
     * accepted.
     *
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @throws SyntaxErrorException in case of the extension is not accepted
     */
    protected function assertFeature(Feature $feature, ReadableInterface $source, int $offset, int $length): void
    {
        if (!\in_array($feature, $this->features, true)) {
            throw SyntaxErrorException::becauseExtensionIsDisabled($source, $feature->name, $offset, $length);
        }
    }

    /**
     * Rejects the GCC spelling of a keyword of C23, the one with two leading
     * underscores (`__const__`, `__thread`), unless the GnuKeywordSpellings
     * feature is accepted.
     *
     * @throws SyntaxErrorException in case of the spelling is not accepted
     */
    protected function assertKeywordSpelling(ReadableInterface $source, TokenInterface $keyword): void
    {
        if (\str_starts_with($keyword->value, '__')) {
            $this->assertFeature(Feature::GnuKeywordSpellings, $source, $keyword->offset, $keyword->size);
        }
    }

    /**
     * Checks the syntax of the source without building the syntax tree.
     *
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexicalErrorException in case of the lexer fails on the source
     * @throws ParserException in case of the lexer fails for any other reason
     */
    public function validate(ReadableInterface $source): bool
    {
        $result = $this->parser->analyze($source, Mode::SyntaxCheck);

        // A partial result is a prefix of the source the grammar has read
        // before it failed
        return $result instanceof SuccessfulResult
            && !$result instanceof PartialResult;
    }
}
