<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\BinaryExpression;

use FFI\Parser\Node\Expression\BinaryExpression;

/**
 * The comma operator: `a, b`, only allowed in parentheses, in a subscript
 * and between `?` and `:` of the conditional operator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.18 Comma operator
 */
final class CommaExpression extends BinaryExpression {}
