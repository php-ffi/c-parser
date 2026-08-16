<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Node;

/**
 * Base of the attributes. The syntax of the attribute is the class of the
 * node: `[[deprecated]]`, `__attribute__((deprecated))`,
 * `__declspec(deprecated)` or a keyword such as `__stdcall`. An attribute
 * specifier gives a node for each attribute it holds and none of its own,
 * so `[[a, b]]` and `[[a]] [[b]]` give the same nodes, and `[[]]` gives
 * none: it has no effect (6.7.13.2p3). The attribute starts at its prefix,
 * or at its name in case of there is none.
 *
 * An attribute is kept where it is written and nothing is checked. Among
 * the declaration specifiers it is one of them. Before a declarator, after
 * its name or after the whole of it, it belongs to the declaration, as GCC
 * has `void exit(int) __attribute__((noreturn));`. After a `*`, or, of the
 * C23 syntax, after an array or a function (6.7.7.2 to 6.7.7.4), it belongs
 * to that type. At the start of a parenthesized declarator it wraps the
 * type the declarator receives into an
 * {@see \FFI\Parser\Node\Type\AttributedType}. After
 * `struct`, `union`, `enum`, the body of a tag or the name of an
 * enumerator it belongs to the tag or the enumerator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13 Attributes
 * @link https://gcc.gnu.org/onlinedocs/gcc/Attribute-Syntax.html GCC, Attribute Syntax
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
         * (6.7.13.2p3), such as `const` in `__attribute__((const))`
         */
        public readonly Identifier $name,
        /**
         * The argument clause, {@see null} in case of none is written, an
         * empty list in case of `()`
         */
        public readonly ?ArgumentList $arguments = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
