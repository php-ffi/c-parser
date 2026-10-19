<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

use FFI\Parser\Node\Expression\ConcatenatedStringExpression;
use FFI\Parser\Node\Literal\StringLiteral;

/**
 * GCC basic assembler statement at the file scope: `__asm__("nop");`, also
 * spelled `__asm`.
 *
 * @link https://gcc.gnu.org/onlinedocs/gcc/Basic-Asm.html GCC, Basic Asm
 */
final class AsmDeclaration extends Declaration implements
    RootDeclarationInterface
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The assembler instructions
         */
        public StringLiteral|ConcatenatedStringExpression $instructions,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
