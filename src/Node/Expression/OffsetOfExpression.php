<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\DesignatorList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * The offset of a member, `__builtin_offsetof(struct S, a.b[2])`, which the
 * `offsetof` macro of `<stddef.h>` expands to.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Offsetof.html GCC, Support for offsetof
 */
final class OffsetOfExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type that holds the member
         */
        public readonly TypeInterface $type,
        /**
         * The path to the member: the name of the first member, which starts
         * where it is written, and the members and the elements after it, as
         * in a designated initializer
         */
        public readonly DesignatorList $path,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
