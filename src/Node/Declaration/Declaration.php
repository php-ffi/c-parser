<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\Node;

/**
 * Base of the declarations: the declarations a header consists of
 * ({@see RootDeclarationInterface}), the members of a structure or a union,
 * the parameters of a function type and the constants of an enumeration.
 * The kind of the declaration is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
abstract class Declaration extends Node implements
    DeclarationInterface {}
