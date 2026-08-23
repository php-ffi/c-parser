<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * Checks the grammar against `FFI::cdef()`: whatever the parser of PHP FFI
 * accepts is accepted by this one too. Only `\FFI\ParserException` means
 * the code is rejected, any other `\FFI\Exception` is the failure to find a
 * function without its library. The parser of PHP FFI checks some semantics
 * too (the attributes it knows, the types declared) and rejects more than a
 * syntax error, most headers of `gcc -E` among them.
 */
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
        foreach (['bzip2', 'cjson', 'egl', 'lua', 'sqlite3', 'zlib', 'zlib-no-markers', 'zstd'] as $name) {
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
        yield 'attributes of GCC that PHP FFI knows' => [
            'struct __attribute__((packed)) P { char c; int i; } __attribute__((aligned(8)));',
        ];
        yield 'calling conventions' => ['typedef int (__cdecl *F)(int); typedef int (__stdcall *G)(int);'];
        yield '__declspec' => ['__declspec(align(16)) struct A { int x; };'];
        yield 'GCC spellings' => [
            '__const int a; int *__restrict p; __inline int f(void); __volatile__ int v;',
        ];
        yield '__extension__' => ['__extension__ typedef long long ll;'];
    }

    #[DataProvider('headersDataProvider')]
    public function testCodeAcceptedByPhpFfiIsAccepted(string $code): void
    {
        // PHP FFI does not know the line markers of the preprocessor
        $stripped = (string) \preg_replace('/^[ \t]*#.*$/m', '', $code);

        try {
            \FFI::cdef($stripped);
        } catch (\FFI\ParserException) {
            self::markTestSkipped('PHP FFI rejects the code');
        } catch (\FFI\Exception) {
            // Parsed, a function cannot be resolved without its library
        }

        self::parse($stripped);
    }
}
