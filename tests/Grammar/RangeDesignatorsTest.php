<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class RangeDesignatorsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function designatorsDataProvider(): iterable
    {
        yield 'range' => ['int a[] = {[0 ... 3] = 1};', '(variable (array int) a = {[0 ... 3]=1})'];
        yield 'bounds of expressions' => [
            'int a[] = {[N - 1 ... N + 1] = 1};',
            '(variable (array int) a = {[(- N 1) ... (+ N 1)]=1})',
        ];
        yield 'after a member' => ['struct S s = {.a[0 ... 1] = 2};', '(variable struct S s = {.a[0 ... 1]=2})'];
    }

    #[DataProvider('designatorsDataProvider')]
    public function testDesignatorIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testRangeIsRejectedAtItsEllipsisWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('int a[] = {[0 ... 3] = 1};');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "RangeDesignators" is not enabled', 14, 3],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
