<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class GenericTypeOperandTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function selectionsDataProvider(): iterable
    {
        yield 'type name' => [
            'int x = _Generic(int, int: 1, default: 0);',
            '(variable int x = (_Generic int int:1 default:0))',
        ];
        yield 'pointer type' => [
            'int x = _Generic(char *, char *: 1);',
            '(variable int x = (_Generic (ptr char) (ptr char):1))',
        ];
        yield 'variable' => [
            'int a; int x = _Generic(a, int: 1);',
            '(variable int a) (variable int x = (_Generic a int:1))',
        ];
        yield 'typedef name' => [
            'typedef int T; int x = _Generic(T, int: 1);',
            '(typedef typedef int T) (variable int x = (_Generic T int:1))',
        ];
    }

    #[DataProvider('selectionsDataProvider')]
    public function testSelectionIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }
}
