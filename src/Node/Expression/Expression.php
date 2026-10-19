<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Node;

/**
 * Base of the expressions: sizes of arrays, values of enumerators, widths of
 * bit-fields, alignments, conditions of `static_assert`, initializers and
 * the operands of `sizeof`, `typeof` and `_Generic`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5 Expressions
 */
abstract class Expression extends Node implements
    ExpressionInterface {}
