<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\EnumType;

use FFI\Parser\Node\NodeList;

/**
 * Enumerators of an enumeration, the body between the braces. May be empty with the Clang Microsoft extensions.
 *
 * @template-extends NodeList<Enumerator>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.3 Enumeration specifiers
 */
final class EnumeratorList extends NodeList {}
