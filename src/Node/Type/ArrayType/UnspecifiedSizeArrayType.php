<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\ArrayType;

use FFI\Parser\Node\Type\ArrayType;

/**
 * Variable length array of unspecified size, `int a[*]`. C allows it only
 * in a parameter of a function prototype, but the parser accepts it
 * everywhere and keeps it for a validator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.3 Array declarators
 */
final class UnspecifiedSizeArrayType extends ArrayType {}
