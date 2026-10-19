<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\UnaryExpression;

use FFI\Parser\Node\Expression\UnaryExpression;

/**
 * GCC imaginary part of a complex number: `__imag__ z`, also spelled
 * `__imag`.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Complex.html GCC, Complex Numbers
 */
final class ImagPartExpression extends UnaryExpression {}
