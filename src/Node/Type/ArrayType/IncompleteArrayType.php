<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\ArrayType;

use FFI\Parser\Node\Type\ArrayType;

/**
 * Array without a size, `int a[]`: an array of unknown size, such as the
 * flexible array member of a structure or an array parameter.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.3 Array declarators
 */
final class IncompleteArrayType extends ArrayType {}
