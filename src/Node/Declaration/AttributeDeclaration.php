<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Declaration of attributes alone, `[[deprecated]];` or
 * `__attribute__((x));`, the attributes of any syntax and of any number of
 * specifiers, none included (`[[]];`).
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
final class AttributeDeclaration extends Declaration implements
    RootDeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The attributes in the order written
         */
        public readonly AttributeList $attributes,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
