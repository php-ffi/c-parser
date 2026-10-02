<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;

/**
 * Place of the node a transformer is given: the nodes that hold it and the
 * whole list of the declarations being transformed.
 */
final class TransformationContext
{
    public function __construct(
        /**
         * The nodes that hold the given one, from a root declaration to the
         * direct parent empty for a root declaration. A node shared by
         * several declarations, such as the structure of `struct S {} a, b;`,
         * is given once, with the parents of its first occurrence.
         *
         * @var list<NodeInterface>
         */
        public readonly array $parents,
        /**
         * Every declaration being transformed, in the order written, as they
         * are given to the transformers
         *
         * @var list<RootDeclarationInterface>
         */
        public readonly array $declarations,
    ) {}
}
