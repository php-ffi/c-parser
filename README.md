# C23 Parser for PHP FFI

<p align="center">
    <a href="https://packagist.org/packages/ffi/c-parser"><img src="https://poser.pugx.org/ffi/c-parser/require/php?style=for-the-badge" alt="PHP 8.1+"></a>
    <a href="https://packagist.org/packages/ffi/c-parser"><img src="https://poser.pugx.org/ffi/c-parser/version?style=for-the-badge" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/ffi/c-parser"><img src="https://poser.pugx.org/ffi/c-parser/v/unstable?style=for-the-badge" alt="Latest Unstable Version"></a>
    <a href="https://packagist.org/packages/ffi/c-parser"><img src="https://poser.pugx.org/ffi/c-parser/downloads?style=for-the-badge" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/php-ffi/c-parser/master/LICENSE"><img src="https://poser.pugx.org/ffi/c-parser/license?style=for-the-badge" alt="License MIT"></a>
</p>
<p align="center">
    <a href="https://github.com/php-ffi/c-parser/actions/workflows/tests.yml"><img src="https://github.com/php-ffi/c-parser/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
</p>

Parses C headers (the ones you pass to `FFI::cdef()`) into an AST. Useful for
generating wrappers and stubs or checking a header before loading it.

Based on C23 specification: [ISO/IEC 9899:2024 (N3220)](https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf)

## Installation

```sh
$ composer require ffi/c-parser
```

## Usage

```php
$parser = new FFI\Parser\Parser();

$declarations = $parser->parse('void move(int dx, int dy);');
// or $parser->parse(new SplFileInfo('header.h'));

echo $declarations[0]->name->value; // "move"
```

An invalid header throws `FFI\Parser\Exception\ParserException`, which points
to the error in the source.

## Benchmarks

| Name              | Speed  | AST Nodes | Header Size |
|-------------------|--------|-----------|-------------|
| bzip2.h           | 0.007s | 3869      | 24.1 KiB    |
| cjson.h           | 0.003s | 1556      | 6.7 KiB     |
| egl.h             | 0.004s | 2694      | 16.4 KiB    |
| lua.h             | 0.013s | 6916      | 39.3 KiB    |
| sqlite3.h         | 0.017s | 8944      | 41.6 KiB    |
| zlib-no-markers.h | 0.011s | 6367      | 33.5 KiB    |
| zlib.h            | 0.012s | 6367      | 50.4 KiB    |
| zstd.h            | 0.004s | 2033      | 18.6 KiB    |
| glfw.h            | 0.185s | 81865     | 344.4 KiB   |
| vulkan.h          | 0.311s | 117375    | 1.05 MiB    |
| windows.h         | 0.396s | 140673    | 1.05 MiB    |

The small headers for tests are in `tests/resources/headers`.

> Please note that these should not be considered benchmarks; results may vary 
> depending on the environment and the PC being used. The measurements were 
> taken in the following environment:
> - PHP 8.5 (with OPCache, with JIT 1205)
> - OS:  Windows 11
> - CPU: AMD Ryzen 9 5900X
> - SSD: SanDisk Ultra II (240GB)

## Extensions

List of supported keywords and instructions defined by various compilers (outside 
the ISO/IEC 9899:2024 standard).

| Extension                 | Example                                 | From       |
|---------------------------|-----------------------------------------|------------|
| Attributes                | `__attribute__((packed))`               | GCC, Clang |
| Declspec                  | `__declspec(dllimport)`                 | MSVC       |
| Calling conventions       | `__cdecl`, `__stdcall`, `__fastcall`    | MSVC       |
| Pointer modifiers         | `__ptr64`, `__unaligned`, `__based(b)`  | MSVC       |
| Pointer authentication    | `__ptrauth(k, a, d)`                    | Clang      |
| Nullability               | `_Nonnull`, `_Nullable`                 | Clang      |
| Block pointers            | `void (^cb)(int)`                       | Clang      |
| Assembler labels          | `int f(void) __asm__("_f");`            | GCC, Clang |
| Keyword spellings         | `__const__`, `__inline__`, `__typeof__` | GCC, Clang |
| `__forceinline`           | `__forceinline int f(void);`            | MSVC       |
| `__extension__`           | `__extension__ typedef long long i64;`  | GCC, Clang |
| Built-in calls            | `__builtin_offsetof(struct S, x)`       | GCC, Clang |
| Statement expressions     | `({ int y = 1; y; })`                   | GCC, Clang |
| Range designators         | `{ [0 ... 3] = 1 }`                     | GCC, Clang |
| `__alignof__` expressions | `__alignof__ x`                         | GCC, Clang |
| Literal suffixes          | `1i64`, `1.0q`, `1.0w`                  | MSVC, GCC  |
| Empty `struct` and `;`    | `struct S {};`, `;`                     | GCC, Clang |

## Limitations

The parser does not track typedef names, so code whose meaning depends on
whether `T` is a type is always parsed one way:

| Code                                                | Current AST                       | Expected AST                                 | When `T` is       |
|-----------------------------------------------------|-----------------------------------|----------------------------------------------|-------------------|
| `(T)-x*y`                                           | `Sub(Paren(T), Mul(x, y))`        | `Mul(Cast(T, Minus(x)), y)`                  | a typedef         |
| `(T)+x`                                             | `Add(Paren(T), x)`                | `Cast(T, Plus(x))`                           | a typedef         |
| `(T)*x`                                             | `Mul(Paren(T), x)`                | `Cast(T, Dereference(x))`                    | a typedef         |
| `(T)&x`                                             | `BitwiseAnd(Paren(T), x)`         | `Cast(T, AddressOf(x))`                      | a typedef         |
| `sizeof(T[3])`                                      | `SizeOf(Paren(Subscript(T, 3)))`  | `SizeOfType(SizedArray(T, 3))`               | a typedef         |
| `typeof(T())`                                       | `TypeOf(Call(T))`                 | `TypeOfType(Function(T))`                    | a typedef         |
| `typeof(T *(U))`                                    | `TypeOf(Mul(T, Paren(U)))`        | `TypeOfType(Function(Pointer(T), Named(U)))` | a typedef, as `U` |
| `sizeof(T *(U))`                                    | `SizeOf(Paren(Mul(T, Paren(U))))` | `SizeOfType(Function(Pointer(T), Named(U)))` | a typedef, as `U` |
| `(T)(x)`                                            | `Cast(T, Paren(x))`               | `Call(Paren(T), x)`                          | a function        |
| `(T)(x, y)`                                         | `Cast(T, Paren(Comma(x, y)))`     | `Call(Paren(T), x, y)`                       | a function        |
| `sizeof(T)`                                         | `SizeOfType(Named(T))`            | `SizeOf(Paren(T))`                           | not a type        |
| `alignof(T)`                                        | `AlignOfType(Named(T))`           | `AlignOf(Paren(T))`                          | not a type        |
| `typeof(T)`                                         | `TypeOfType(Named(T))`            | `TypeOf(T)`                                  | not a type        |
| `alignas(T)`                                        | `AlignAsType(Named(T))`           | `AlignAs(T)`                                 | not a type        |
| `_Generic(T, …)`                                    | `Generic(T, …)`                   | `Generic(Named(T), …)`                       | a typedef         |
| `__builtin_offsetof(T, a)`, any `__builtin_*(T, …)` | `Builtin(T, a)`                   | `Builtin(Named(T), a)`                       | a typedef         |
| `int f(int (T));`                                   | `Parameter(int, name: T)`         | `Parameter(Function(int, Named(T)))`         | a typedef         |

The parentheses are kept in the tree (`Paren`), so it can be rebuilt once the
typedef names are known.

I.e. what's actually needed is a Visitor that will mutate the AST based on the 
presence of a declaration. For now, this is a TODO :)
