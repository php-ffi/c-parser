<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList;
use FFI\Parser\Node\Identifier;

/**
 * Attribute of the C23 syntax, `[[deprecated("use g")]]` or
 * `[[gnu::aligned(8)]]`, its digraph spelling `<:<:...:>:>` included.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13.2 General
 */
final class StandardAttribute extends Attribute
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The attribute prefix before `::`, such as `gnu` in `gnu::packed`,
         * {@see null} in case of none is written
         */
        public readonly ?Identifier $prefix,
        Identifier $name,
        ?ArgumentList $arguments = null,
        int $offset = 0,
    ) {
        parent::__construct($name, $arguments, $offset);
    }
}
