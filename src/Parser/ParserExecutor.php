<?php

declare(strict_types=1);

namespace FFI\Parser\Parser;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Exception\LexicalErrorException;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserSourceException;

/**
 * The generated parser with the syntax check opened up.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 *
 * @template-extends CompiledParserExecutor<list<RootDeclarationInterface>>
 */
final class ParserExecutor extends CompiledParserExecutor
{
    public function __construct(
        protected ParserConfiguration $config,
    ) {
        parent::__construct();
    }

    public function withConfiguration(ParserConfiguration $config): self
    {
        $self = clone $this;
        /** @phpstan-ignore-next-line : Allow readonly modification */
        $self->config = $config;

        return $self;
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
