<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\NodeInterface;

/**
 * Node that is an expression. Every {@see Expression} is one, and a node of
 * another kind may be one as well.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.1 General
 */
interface ExpressionInterface extends NodeInterface {}
