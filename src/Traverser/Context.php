<?php

declare(strict_types=1);

namespace FFI\Parser\Traverser;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Traverser;

/**
 * Place of the node a visitor is given: the nodes that hold it and the
 * whole list of the declarations being walked.
 */
final class Context
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
         * Every declaration being walked, in the order written, as given to
         * {@see Traverser::traverse}
         *
         * @var list<RootDeclarationInterface>
         */
        public readonly array $declarations,
    ) {}
}
