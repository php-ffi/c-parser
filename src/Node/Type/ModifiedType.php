<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Type\ModifiedType\TypeModifier;

/**
 * Type written with modifiers, such as `unsigned long long int` or `long double`.
 * The modifiers are not checked, the qualifiers among them go to a {@see QualifiedType} around the node.
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
         * Modifiers in the order written, repeats included
         *
         * @var non-empty-list<TypeModifier>
         */
        public array $modifiers,
        /**
         * The base, {@see null} if only modifiers are written, such as `unsigned`
         */
        public ?TypeInterface $type = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
