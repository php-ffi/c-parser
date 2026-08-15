<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Type\ModifiedType\TypeModifier;

/**
 * Type written with the modifiers of its base, such as `unsigned long long
 * int` or `long double`. The modifiers are kept as written and are not
 * checked, so `long char` gives a node as well. The qualifiers written
 * among the modifiers (`unsigned const long`) go to a {@see QualifiedType}
 * around this node. The base is a typedef name only when the modifiers
 * follow it (`T unsigned`), since an identifier after a modifier is a
 * declarator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 */
final class ModifiedType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The modifiers in the order written, repeats included
         *
         * @var non-empty-list<TypeModifier>
         */
        public readonly array $modifiers,
        /**
         * The base, {@see null} in case of only the modifiers are written,
         * such as `unsigned` or `long long`
         */
        public readonly ?TypeInterface $type = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
