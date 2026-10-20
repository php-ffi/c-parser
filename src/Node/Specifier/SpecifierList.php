<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier;

use FFI\Parser\Node\Attribute\AttributeInterface;
use FFI\Parser\Node\NodeList;

/**
 * Specifiers of a declaration or a type name outside the type, and the attributes around them, in the order
 * written, repeats included.
 *
 * @template-extends NodeList<SpecifierInterface|AttributeInterface>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
final class SpecifierList extends NodeList {}
