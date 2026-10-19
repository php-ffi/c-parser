<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Node;

/**
 * Base of the attributes. The syntax of the attribute is the class of the
 * node: `[[deprecated]]`. An attribute specifier gives a node for each
 * attribute it holds and none of its own, so `[[a, b]]` and `[[a]] [[b]]`
 * give the same nodes, and `[[]]` gives none: it has no effect
 * (6.7.13.2p3). The attribute starts at its prefix, or at its name in case
 * of there is none.
 *
 * An attribute is kept where it is written and nothing is checked. Before
 * the declaration specifiers and after the last of them it is one of them.
 * After the name of a declarator it belongs to the declaration. After a
 * `*`, an array or a function (6.7.7.2 to 6.7.7.4) it belongs to that type.
 * After `struct`, `union`, `enum` or the name of an enumerator it belongs
 * to the tag or the enumerator. The GCC attributes at the start of a
 * parenthesized declarator wrap the type it receives in a
 * {@see \FFI\Parser\Node\Type\AttributedType}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13 Attributes
 */
abstract class Attribute extends Node implements
    AttributeInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the attribute as written. A keyword is a name as well
         * (6.7.13.2p3), such as `const` in `[[gnu::const]]`
         */
        public Identifier $name,
        /**
         * The argument clause, {@see null} in case of none is written, an
         * empty list in case of `()`
         */
        public ?ArgumentList $arguments = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
