<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer;
use FFI\Parser\Node\NodeList;

/**
 * Initializer in braces (the braced initializer of C23), its items in the
 * order written: `{ 1, .b = 2, [3] = { 4 } }`. The braces may be empty,
 * `{}`. Unlike the other lists it starts where its brace does, as the brace
 * is a part of it.
 *
 * @template-extends NodeList<ExpressionInterface|InitializerList|DesignatedInitializer>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 */
final class InitializerList extends NodeList {}
