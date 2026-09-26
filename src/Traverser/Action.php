<?php

declare(strict_types=1);

namespace FFI\Parser\Traverser;

use FFI\Parser\Traverser;

/**
 * Instruction of a visitor to the walk, returned in place of a node.
 */
enum Action
{
    /**
     * The children of the node are not walked. Allowed on entering the node
     * only, the other visitors still enter and leave the node itself.
     */
    case SkipChildren;

    /**
     * The node is removed from the list that holds it or from the result of
     * {@see Traverser::traverse} in case of a root declaration.
     *
     * The visitors that follow are not called for the node.
     */
    case Remove;
}
