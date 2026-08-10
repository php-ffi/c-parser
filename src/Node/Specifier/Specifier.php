<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier;

use FFI\Parser\Node\Node;

/**
 * Base of the declaration specifiers that are not a part of the type: the
 * storage-class, function and alignment specifiers. The type specifiers and
 * the qualifiers build the type instead. The kind of the specifier is the
 * class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
abstract class Specifier extends Node {}
