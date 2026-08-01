<?php

declare(strict_types=1);

namespace FFI\Parser\Node;

/**
 * Root of the syntax tree, i.e. the whole preprocessed header (the
 * {@code translation-unit} of the C23 grammar).
 */
final class TranslationUnit extends Node
{
    /**
     * @param list<Node> $declarations External declarations in the order
     *        they are written. Empty declarations (a lone {@code ;}) are not
     *        listed.
     * @param int<0, max> $offset
     */
    public function __construct(
        public readonly array $declarations,
        int $offset,
    ) {
        parent::__construct($offset);
    }
}
