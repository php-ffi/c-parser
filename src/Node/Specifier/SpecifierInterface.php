<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier;

use FFI\Parser\Node\NodeInterface;

/**
 * Node that is a declaration specifier outside the type.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
interface SpecifierInterface extends NodeInterface {}
