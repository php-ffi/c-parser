<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Node;

/**
 * Base of the expressions. In a header they are constant expressions only:
 * sizes of arrays, values of enumerators, widths of bit-fields, alignments,
 * conditions of `static_assert` and initializers.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.6 Constant expressions
 */
abstract class Expression extends Node {}
