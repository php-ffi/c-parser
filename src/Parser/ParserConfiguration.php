<?php

declare(strict_types=1);

namespace FFI\Parser\Parser;

final class ParserConfiguration
{
    public function __construct(
        /**
         * @var list<non-empty-string>
         */
        public readonly array $abi = [],
    ) {}

    /**
     * @param non-empty-string $keyword
     */
    public function withAddedAbi(string $keyword): self
    {
        return new self(
            abi: [...$this->abi, $keyword],
        );
    }
}
