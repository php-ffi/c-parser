<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\BuiltinType;

use FFI\Parser\Node\Type\BuiltinType;

/**
 * The `__int128` type, a 128-bit integer type of GCC.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/_005f_005fint128.html GCC, 128-bit Integers
 */
final class Int128Type extends BuiltinType {}
