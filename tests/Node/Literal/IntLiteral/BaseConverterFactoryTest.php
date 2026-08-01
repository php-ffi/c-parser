<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal\IntLiteral;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverterFactory\GmpBaseConverter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[CoversClass(BaseConverterFactory::class)]
final class BaseConverterFactoryTest extends TestCase
{
    #[RequiresPhpExtension('gmp')]
    public function testGmpConverterIsPreferred(): void
    {
        self::assertInstanceOf(GmpBaseConverter::class, BaseConverterFactory::createDefault());
    }

    public function testDefaultConverterIsSelectedOnce(): void
    {
        self::assertSame(BaseConverterFactory::createDefault(), BaseConverterFactory::createDefault());
    }
}
