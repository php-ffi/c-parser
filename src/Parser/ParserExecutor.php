<?php

declare(strict_types=1);

namespace FFI\Parser\Parser;

use FFI\Parser\Node\Node;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;

/**
 * The generated parser with the syntax check opened up.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser
 *
 * @template-extends CompiledParserExecutor<list<Node>>
 */
final class ParserExecutor extends CompiledParserExecutor
{
    /**
     * Checks the syntax of the source without building the syntax tree.
     *
     * @throws LexerExceptionInterface in case of the source cannot be read
     */
    public function validate(ReadableInterface $source): bool
    {
        $result = $this->parser->analyze($source, Mode::SyntaxCheck);

        // A partial result is a prefix of the source the grammar has read
        // before it failed, so the source as a whole is not valid.
        return $result instanceof SuccessfulResult
            && !$result instanceof PartialResult;
    }
}
