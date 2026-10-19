<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

/**
 * Attribute of the GCC syntax, `__attribute__((aligned(8)))`, also spelled
 * `__attribute`. Among the declaration specifiers it is one of them, after
 * the whole declarator it belongs to the declaration, after the body of a
 * structure, union or enumeration it belongs to the tag.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Attribute-Syntax.html GCC, Attribute Syntax
 */
final class GnuAttribute extends Attribute {}
