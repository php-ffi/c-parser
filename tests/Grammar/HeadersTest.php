<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class HeadersTest extends TestCase
{
    private const HEADERS = __DIR__ . '/../resources/headers/';

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, Preset}>
     */
    public static function headersDataProvider(): iterable
    {
        yield 'bzip2' => ['bzip2.h', 210, Preset::gcc()];
        yield 'cJSON' => ['cjson.h', 85, Preset::gcc()];
        yield 'EGL' => ['egl.h', 214, Preset::c23()];
        yield 'Lua' => ['lua.h', 388, Preset::gcc()];
        yield 'SQLite' => ['sqlite3.h', 355, Preset::c23()];
        yield 'Windows' => ['windows.h', 6907, Preset::msvc()];
        yield 'zlib' => ['zlib.h', 373, Preset::gcc()];
        yield 'zlib without line markers' => ['zlib-no-markers.h', 373, Preset::gcc()];
        yield 'zstd' => ['zstd.h', 91, Preset::gcc()];
    }

    /**
     * @param non-empty-string $file
     * @param int<0, max> $declarations
     */
    #[DataProvider('headersDataProvider')]
    public function testHeaderOfLibraryIsParsedWithoutViolations(string $file, int $declarations, Preset $preset): void
    {
        self::assertCount($declarations, (new Parser(preset: $preset))->parse(self::read($file)));
    }

    public function testLineMarkersDoNotChangeTheTree(): void
    {
        $print = static fn(string $file): array => \array_map(
            static fn(NodeInterface $node): string => (string) \preg_replace('/\s+/', ' ', NodePrinter::print($node)),
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
