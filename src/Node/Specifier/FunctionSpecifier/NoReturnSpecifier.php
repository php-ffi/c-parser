<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier\FunctionSpecifier;

use FFI\Parser\Node\Specifier\FunctionSpecifier;

/**
 * The `_Noreturn` function specifier.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.5 Function specifiers
 */
final class NoReturnSpecifier extends FunctionSpecifier {}
