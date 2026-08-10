<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverter;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\GmpBaseConverter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[CoversClass(BaseConverter::class)]
final class BaseConverterTest extends TestCase
{
    #[RequiresPhpExtension('gmp')]
    public function testGmpConverterIsPreferred(): void
    {
        self::assertInstanceOf(GmpBaseConverter::class, BaseConverter::createDefault());
    }

    public function testDefaultConverterIsSelectedOnce(): void
    {
        self::assertSame(BaseConverter::createDefault(), BaseConverter::createDefault());
    }
}
