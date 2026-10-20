<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\Node;

/**
 * Base of the declarations: the root ones, the members of a structure or a union, the parameters and the
 * enumerators.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
abstract class Declaration extends Node implements
    DeclarationInterface {}
