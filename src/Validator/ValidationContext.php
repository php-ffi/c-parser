<?php

declare(strict_types=1);

namespace FFI\Parser\Validator;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;

/**
 * Place of the node a validator is given.
 */
final class ValidationContext
{
    public function __construct(
        /**
         * Nodes that hold the given one, from a root declaration to the direct
         * parent, empty for a root declaration. A node shared by several
         * declarations (`struct S {} a, b;`) has the parents of its first
         * occurrence.
         *
         * @var list<NodeInterface>
         */
        public readonly array $parents,
        /**
         * @var list<RootDeclarationInterface>
         */
        public readonly array $declarations,
    ) {}
}
