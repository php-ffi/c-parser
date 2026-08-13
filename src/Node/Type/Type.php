<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Node;

/**
 * Base of the types, built of the type specifiers and the qualifiers of a
 * declaration, of a member or of a type name, and of its declarator, which
 * wraps them into pointers, arrays and functions.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3 Type specifiers
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7 Declarators
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.8 Type names
 */
abstract class Type extends Node {}
