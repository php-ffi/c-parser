<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\NodeInterface;

/**
 * Node that is a declaration: it declares a name, a tag or nothing at all,
 * as `static_assert` and `int;` do. Every {@see Declaration} is one, and a
 * node of another kind may be one as well, such as a structure type that
 * declares its tag.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
interface DeclarationInterface extends NodeInterface {}
