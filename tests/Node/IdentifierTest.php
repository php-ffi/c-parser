<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDeclaration;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Identifier::class)]
final class IdentifierTest extends TestCase
{
    public function testIdentifierIsConvertedToItsName(): void
    {
        $declaration = self::parseSingle('int sqlite3_open(const char *name);', FunctionDeclaration::class);

        self::assertSame('sqlite3_open', (string) $declaration->name);
        self::assertSame('name: sqlite3_open', 'name: ' . $declaration->name);
    }
}
