<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\UnaryExpression;

use FFI\Parser\Node\Expression\UnaryExpression;

/**
 * Negation: `-a`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.4.3 Unary arithmetic operators
 */
final class MinusExpression extends UnaryExpression {}
