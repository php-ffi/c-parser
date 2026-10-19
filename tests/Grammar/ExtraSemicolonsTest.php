<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class ExtraSemicolonsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function semicolonsDataProvider(): iterable
    {
        yield 'after a declaration' => ['int a;;', ['(variable int a)']];
        yield 'before a declaration' => [';;int a;', ['(variable int a)']];
        yield 'alone' => [';', []];
        yield 'among the members' => ['struct S { int a;; int b; };', ['(tag struct S { int a; int b })']];
        yield 'alone among the members' => ['struct S { ; };', ['(tag struct S {  })']];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('semicolonsDataProvider')]
    public function testSemicolonLeavesNoTrace(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }
}
