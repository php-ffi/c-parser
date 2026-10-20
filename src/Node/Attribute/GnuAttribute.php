<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

/**
 * GCC attribute, `__attribute__((aligned(8)))`, also spelled `__attribute`.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Attribute-Syntax.html GCC, Attribute Syntax
 */
final class GnuAttribute extends Attribute {}
