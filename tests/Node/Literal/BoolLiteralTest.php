<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal;

use FFI\Parser\Node\Literal\BoolLiteral;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BoolLiteral::class)]
final class BoolLiteralTest extends TestCase
{
    public function testBuiltConstantGetsSourceText(): void
    {
        self::assertSame(['true', 'false'], [(new BoolLiteral(true))->raw, (new BoolLiteral(false))->raw]);
    }
}
