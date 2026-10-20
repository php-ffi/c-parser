<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Transformer\TransformationContext\Scope;

/**
 * Place of the node a transformer is given.
 */
final class TransformationContext
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
        private readonly Scope $scope = new Scope(),
    ) {}

    /**
     * Whether the name is declared as a typedef name before the node.
     */
    public function isTypedefName(string $name): bool
    {
        return $this->scope->isTypedefName($name);
    }

    /**
     * Whether the name is declared as an object, a function, an enumeration
     * constant or a parameter before the node. False for an undeclared name.
     */
    public function isNonTypeName(string $name): bool
    {
        return $this->scope->isNonTypeName($name);
    }
}
