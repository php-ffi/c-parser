<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\NullptrLiteral;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NullptrLiteral::class)]
final class NullptrLiteralTest extends TestCase
{
    public function testBuiltConstantGetsSourceText(): void
    {
        self::assertSame('nullptr', (new NullptrLiteral())->raw);
    }
}
