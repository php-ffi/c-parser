<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier;

/**
 * Base of the alignment specifiers, `alignas(...)`, also spelled
 * `_Alignas`. Whether the operand is a type or an expression is the class
 * of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.6 Alignment specifier
 */
abstract class AlignmentSpecifier extends Specifier {}
