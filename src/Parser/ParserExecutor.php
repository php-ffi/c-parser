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
    /**
     * Spellings of the MsKeywordSpellings feature.
     *
     * @var list<non-empty-string>
     */
    private const MS_KEYWORD_SPELLINGS = ['__inline', '__restrict', '__alignof', '__typeof__', '__typeof_unqual__'];

    /**
     * The characters after a backslash that start an escape sequence of C23, 6.4.4.5.
     */
    private const C23_ESCAPE_CHARACTERS = '\'"?\\abfnrtv01234567xuU';

    public function __construct(
        /**
         * @var list<Feature>
         */
        private readonly array $features = [],
    ) {
        parent::__construct();
    }

    /**
     * Rejects the fragment of an extension the preset does not accept.
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
     * Rejects a spelling of a keyword with two leading underscores (`__const__`,
     * `__inline`) unless the GnuKeywordSpellings or MsKeywordSpellings feature
     * accepts it.
     *
     * @throws SyntaxErrorException in case of the spelling is not accepted
     */
    protected function assertKeywordSpelling(ReadableInterface $source, TokenInterface $keyword): void
    {
        if (!\str_starts_with($keyword->value, '__')) {
            return;
        }

        if (\in_array($keyword->value, self::MS_KEYWORD_SPELLINGS, true)
            && \in_array(Feature::MsKeywordSpellings, $this->features, true)) {
            return;
        }

        $this->assertFeature(Feature::GnuKeywordSpellings, $source, $keyword->offset, $keyword->size);
    }

    /**
     * Rejects the escape sequences of the extensions in a character constant or a string literal: `\e` unless the
     * EscapeE or UnknownEscapes feature accepts it, any other one of no meaning in C23 (`\q`) unless the
     * UnknownEscapes feature accepts it.
     *
     * @throws SyntaxErrorException in case of an escape sequence is not accepted
     */
    protected function assertEscapeSequences(ReadableInterface $source, TokenInterface $literal): void
    {
        if (\in_array(Feature::UnknownEscapes, $this->features, true)) {
            return;
        }

        // A backslash and the character after it, an escaped backslash is one pair, a line splice has no character
        \preg_match_all('/\\\\(?:\r?\n|(.))/', $literal->value, $escapes);

        foreach ($escapes[1] as $character) {
            if ($character === '' || \str_contains(self::C23_ESCAPE_CHARACTERS, $character)) {
                continue;
            }

            $feature = $character === 'e' || $character === 'E' ? Feature::EscapeE : Feature::UnknownEscapes;

            $this->assertFeature($feature, $source, $literal->offset, $literal->size);
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
