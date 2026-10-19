<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class AsmLabelsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function labelsDataProvider(): iterable
    {
        yield 'function' => ['extern int f(void) __asm__("_f");', ['(function extern (fn int (void)) f __asm__("_f"))']];
        yield 'short spelling' => ['int f(void) __asm("_f");', ['(function (fn int (void)) f __asm__("_f"))']];
        yield 'concatenated name and attributes after it' => [
            'int fscanf(const char *, ...) __asm__("" "__isoc99_fscanf") __attribute__((__warn_unused_result__));',
            [
                '(function (fn int ((ptr [const char]), ...)) fscanf __asm__((concat "" "__isoc99_fscanf"))'
                . ' __attribute__((__warn_unused_result__)))',
            ],
        ];
        yield 'variables with initializers' => [
            'int x __asm("y") = 1, z __asm__("w");',
            ['(variable int x __asm__("y") = 1)', '(variable int z __asm__("w"))'],
        ];
        yield 'function definition' => [
            'int f(void) __asm__("g") { return 0; }',
            ['(definition (fn int (void)) f __asm__("g") { return 0; })'],
        ];
        yield 'asm as a name' => ['int asm;', ['(variable int asm)']];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('labelsDataProvider')]
    public function testLabelIsParsed(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidLabelsDataProvider(): iterable
    {
        yield 'name without quotes' => ['int x __asm__(y);', 14, 'y'];
        yield 'after the attributes of a function' => ['int f(void) __attribute__((x)) __asm__("g");', 31, '__asm__'];
        yield 'member' => ['struct S { int a __asm__("b"); };', 17, '__asm__'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidLabelsDataProvider')]
    public function testInvalidLabelIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
