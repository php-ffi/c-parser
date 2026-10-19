<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\UnaryExpression;

use FFI\Parser\Node\Expression\UnaryExpression;

/**
 * GCC real part of a complex number: `__real__ z`, also spelled `__real`.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Complex.html GCC, Complex Numbers
 */
final class RealPartExpression extends UnaryExpression {}
