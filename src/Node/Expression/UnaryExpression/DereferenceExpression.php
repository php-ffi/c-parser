<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\UnaryExpression;

use FFI\Parser\Node\Expression\UnaryExpression;

/**
 * The indirection operator: `*p`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.4.2 Address and indirection operators
 */
final class DereferenceExpression extends UnaryExpression {}
