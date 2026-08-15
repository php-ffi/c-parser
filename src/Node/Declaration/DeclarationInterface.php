<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\NodeInterface;

/**
 * Node that declares a name. Every {@see Declaration} is one, and a node of
 * another kind, such as a type or an expression, may be one as well.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
interface DeclarationInterface extends NodeInterface {}
