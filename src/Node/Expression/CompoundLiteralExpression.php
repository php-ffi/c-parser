<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Initializer\InitializerList;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Unnamed object of a type initialized by a list, `(int[]){1, 2}`, with the
 * storage-class specifiers written before the type name,
 * `(static int){1}`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.6 Compound literals
 */
final class CompoundLiteralExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the object
         */
        public TypeInterface $type,
        /**
         * The initializer in braces
         */
        public InitializerList $initializer,
        /**
         * The storage-class specifiers before the type name, {@see null} in
         * case of none is written
         */
        public ?SpecifierList $specifiers = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
