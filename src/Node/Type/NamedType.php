<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Identifier;

/**
 * Type referred to by a name declared elsewhere, such as a typedef name.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.9 Type definitions
 */
final class NamedType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public Identifier $name,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
