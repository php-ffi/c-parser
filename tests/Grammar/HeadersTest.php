<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\Node;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the grammar against the headers of real libraries in
 * `tests/resources/headers`: the output of `gcc -E` (with the line markers
 * and, for zlib, without them) on Ubuntu 24.04 with gcc 13, of libraries
 * under permissive licenses. Every header is parsed, the number of the
 * declarations at file scope is the one that has been counted once.
 */
#[CoversNothing]
final class HeadersTest extends TestCase
{
    private const HEADERS = __DIR__ . '/../resources/headers/';

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function headersDataProvider(): iterable
    {
        yield 'bzip2' => ['bzip2.h', 210];
        yield 'cJSON' => ['cjson.h', 85];
        yield 'EGL' => ['egl.h', 214];
        yield 'Lua' => ['lua.h', 388];
        yield 'SQLite' => ['sqlite3.h', 355];
        yield 'zlib' => ['zlib.h', 373];
        yield 'zlib without line markers' => ['zlib-no-markers.h', 373];
        yield 'zstd' => ['zstd.h', 91];
    }

    /**
     * @param non-empty-string $file
     * @param int<0, max> $declarations
     */
    #[DataProvider('headersDataProvider')]
    public function testHeaderOfLibraryIsParsed(string $file, int $declarations): void
    {
        self::assertCount($declarations, self::parse(self::read($file)));
    }

    /**
     * The trees are compared without the whitespace: `gcc -E -P` also drops
     * the empty lines, which the bodies of the functions keep as text.
     */
    public function testLineMarkersDoNotChangeTheTree(): void
    {
        $print = static fn(string $file): array => \array_map(
            static fn(Node $node): string => (string) \preg_replace('/\s+/', ' ', NodePrinter::print($node)),
            self::parse(self::read($file)),
        );

        self::assertSame($print('zlib.h'), $print('zlib-no-markers.h'));
    }

    /**
     * @param non-empty-string $file
     */
    private static function read(string $file): string
    {
        $code = \file_get_contents(self::HEADERS . $file);

        self::assertIsString($code);

        return $code;
    }
}
