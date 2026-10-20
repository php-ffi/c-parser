<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Node;

/**
 * Base of the attributes. The syntax is the class of the node, `[[deprecated]]`.
 * An attribute specifier gives a node for each attribute it holds, `[[]]` gives none.
 * An attribute is kept where it is written and nothing is checked.
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
         * The name as written, a keyword included, `const` in `[[gnu::const]]`
         */
        public Identifier $name,
        /**
         * The argument clause, {@see null} if not written, empty for `()`
         */
        public ?ArgumentList $arguments = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
