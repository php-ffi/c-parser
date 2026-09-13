<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer;

use FFI\Parser\Node\NodeInterface;

/**
 * Node that is an initializer, an expression or a list in braces: the
 * initial value of a declared one, an item of a list in braces and the
 * value of a designated initializer.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 */
interface InitializerInterface extends NodeInterface {}
