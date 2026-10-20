<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer\InitializerList;

use FFI\Parser\Node\Initializer\InitializerInterface;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\DesignatorList;
use FFI\Parser\Node\Node;

/**
 * Item of an initializer list that names the initialized subobject,
 * `.a.b[2] = 1`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 */
final class DesignatedInitializer extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public DesignatorList $designators,
        public InitializerInterface $initializer,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
