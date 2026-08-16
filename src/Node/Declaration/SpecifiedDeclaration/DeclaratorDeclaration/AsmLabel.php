<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;

use FFI\Parser\Node\Literal\ConcatenatedStringLiteral;
use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Node\Node;

/**
 * The name of the symbol of a declaration in the object file,
 * `__asm__("_f")` in `int f(void) __asm__("_f");`, also spelled `__asm`.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Asm-Labels.html GCC, Controlling Names Used in Assembler Code
 */
final class AsmLabel extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the symbol
         */
        public readonly StringLiteral|ConcatenatedStringLiteral $name,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
