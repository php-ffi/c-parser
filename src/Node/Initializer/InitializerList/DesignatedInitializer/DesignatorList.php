<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer;

use FFI\Parser\Node\NodeList;

/**
 * Designators of a designated initializer, `.a[2]` in `.a[2] = 1`. C
 * requires at least one.
 *
 * @template-extends NodeList<Designator>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 */
final class DesignatorList extends NodeList {}
