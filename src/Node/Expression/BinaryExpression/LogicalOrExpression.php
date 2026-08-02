<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\BinaryExpression;

use FFI\Parser\Node\Expression\BinaryExpression;

/**
 * Logical OR: `a || b`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.15 Logical OR operator
 */
final class LogicalOrExpression extends BinaryExpression {}
