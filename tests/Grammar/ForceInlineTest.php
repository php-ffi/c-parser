<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class ForceInlineTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function specifiersDataProvider(): iterable
    {
        yield 'function definition' => [
            'static __forceinline int f(void) { return 0; }',
            '(definition static __forceinline (fn int (void)) f { return 0; })',
        ];
        yield 'before the type specifier' => ['__forceinline void g(void);', '(function __forceinline (fn void (void)) g)'];
        yield 'after the type specifier' => ['int __forceinline h(void);', '(function __forceinline (fn int (void)) h)'];
    }

    #[DataProvider('specifiersDataProvider')]
    public function testSpecifierIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(\array_last(self::parse($code))));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function invalidPlacesDataProvider(): iterable
    {
        yield 'after the declarator' => ['int f(void) __forceinline;', 12];
        yield 'member' => ['struct S { __forceinline int a; };', 11];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidPlacesDataProvider')]
    public function testSpecifierOutOfPlaceIsRejected(string $code, int $offset): void
    {
        self::assertSyntaxError($code, $offset, '__forceinline');
    }
}
