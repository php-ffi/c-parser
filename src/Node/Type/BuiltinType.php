<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

/**
 * Base of the types a type specifier keyword names by itself, with no tag,
 * member list or type operand, such as `int` or `_BitInt(8)`. The kind of
 * the type is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 */
abstract class BuiltinType extends Type {}
