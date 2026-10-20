<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier;

use FFI\Parser\Node\Node;

/**
 * Base of the declaration specifiers outside the type: storage-class, function and alignment ones.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
abstract class Specifier extends Node implements
    SpecifierInterface {}
