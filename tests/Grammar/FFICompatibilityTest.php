<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[CoversNothing]
#[RequiresPhpExtension('ffi')]
final class FFICompatibilityTest extends TestCase
{
    private const HEADERS = __DIR__ . '/../resources/headers/';

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function headersDataProvider(): iterable
    {
        foreach (['cjson', 'egl', 'sqlite3'] as $name) {
            $code = \file_get_contents(self::HEADERS . $name . '.h');

            self::assertIsString($code);
            self::assertNotSame('', $code);

            yield 'header ' . $name => [$code];
        }

        yield 'scalar types' => ['typedef unsigned long size_t; typedef _Bool b; long double ld; signed char sc;'];
        yield 'function prototypes' => ['int printf(const char *format, ...); void *malloc(size_t size);'];
        yield 'structure with bit-fields and a flexible array' => [
            'struct S { unsigned a : 3, : 2; int b; char data[]; };',
        ];
        yield 'unions and anonymous members' => ['union U { struct { int x, y; }; float f[2]; };'];
        yield 'enumeration with values' => ['enum E { A = 1 << 2, B, C = A | B, D = sizeof(int) };'];
        yield 'pointers to functions' => [
            'typedef void (*callback_t)(void *ctx, int code); int (*signal(int, void (*)(int)))(int);',
        ];
        yield 'qualifiers' => ['extern const char *const restrict names[16]; volatile int v;'];
        yield 'last member without its semicolon' => ['struct S { int a; int b }; union U { int a };'];
        yield 'increment in an initializer' => ['int y; int x = y++; int z = --y;'];
        yield 'calling convention after the declarator' => [
            'int f(void) __cdecl; typedef int F(void) __stdcall; int g(void) __asm__("x") __cdecl;',
        ];
        yield 'calling convention after struct' => ['struct __cdecl S { int a; };'];
        yield 'keywords as names' => [
            'int bool; struct true { int a; }; enum { false, nullptr }; int f(int alignas, int typeof);',
        ];
        yield 'keywords as enumerators' => ['enum E { constexpr, thread_local, __forceinline, __ptr64, __thread };'];
    }

    #[DataProvider('headersDataProvider')]
    public function testCodeAcceptedByPhpFfiIsAccepted(string $code): void
    {
        $stripped = (string) \preg_replace('/^[ \t]*#.*$/m', '', $code);

        try {
            \FFI::cdef($stripped);
        } catch (\FFI\ParserException) {
            self::markTestSkipped('PHP FFI rejects the code');
        } catch (\FFI\Exception) {
        }

        self::assertNotSame([], (new Parser(preset: Preset::php()))->parse($stripped));
    }
}
