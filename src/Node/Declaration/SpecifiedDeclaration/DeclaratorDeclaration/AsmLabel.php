<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration;

use FFI\Parser\Node\Expression\ConcatenatedStringExpression;
use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Node\Node;

/**
 * The symbol name of a declaration in the object file, `__asm__("_f")` in `int f(void) __asm__("_f");`.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Asm-Labels.html GCC, Controlling Names Used in Assembler Code
 */
final class AsmLabel extends Node
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public StringLiteral|ConcatenatedStringExpression $name,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
