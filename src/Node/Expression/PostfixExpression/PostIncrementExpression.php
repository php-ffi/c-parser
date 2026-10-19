<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\PostfixExpression;

use FFI\Parser\Node\Expression\PostfixExpression;

/**
 * Postfix increment: `a++`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.5 Postfix increment and decrement operators
 */
final class PostIncrementExpression extends PostfixExpression {}
