<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Type\QualifiedType\TypeQualifier;

/**
 * Type with qualifiers, such as `const volatile int` or `int *const`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.4 Type qualifiers
 */
final class QualifiedType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * Qualifiers in the order written, repeats included
         *
         * @var non-empty-list<TypeQualifier>
         */
        public array $qualifiers,
        /**
         * The qualified type, {@see null} if no type specifier is written, `const;`
         */
        public ?TypeInterface $type = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
