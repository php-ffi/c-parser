<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\NodeList;

/**
 * Attributes of a declaration, a member, a parameter, a tag, an enumerator
 * or a type, in the order written, of all the attribute specifiers
 * written there. A node without attributes has {@see null} instead, only
 * an attribute declaration of empty specifiers (`[[]];`) has an empty
 * list, which starts where the declaration does.
 *
 * @template-extends NodeList<AttributeInterface>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13.2 General
 */
final class AttributeList extends NodeList {}
