# C23 Parser for PHP

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

### AST Transformers

The parser contains a set of built-in AST transformations. To use your own,
pass it to the constructor:

```php
// override transformers
$parser = new FFI\Parser\Parser(transformerRules: [
    new MyAstTransformer(),
]);

// or extend existing ones
$parser = new FFI\Parser\Parser(
    transformerRules: FFI\Parser\Transformer::getDefaultRules([
        new MyAstTransformer(),
    ]),
);
```

### AST Validators

The parser checks the AST with validators after the transformations. To use
your own, pass it to the constructor:

```php
// override rules
$parser = new FFI\Parser\Parser(validatorRules: [
    new MyValidatorRule(),
]);

// or extend existing ones
$parser = new FFI\Parser\Parser(
    validatorRules: FFI\Parser\Validator::getDefaultRules([
        new MyValidatorRule(),
    ]),
);
```

### Compiler Presets

A preset lists the typedef names a compiler knows without any header. 

By default, the parser knows the names of Clang, GCC, MSVC and PHP FFI. To use 
only some of them, pass a preset to the constructor:

```php
use FFI\Parser\Preset;
use FFI\Parser\Preset\Feature;

$parser = new FFI\Parser\Parser(preset: new Preset(
    features: [Feature::ComplexIntegers],
    typedefNames: ['uint8_t', 'size_t'],
));
```

The presets of the compilers are:
- `FFI\Parser\Preset::gcc()` - only GCC features
- `FFI\Parser\Preset::clang()` - only Clang features
- `FFI\Parser\Preset::msvc()` - only MSVC features
- `FFI\Parser\Preset::php()` - only PHP FFI features
- `FFI\Parser\Preset::c23()` - only C23 features
- `FFI\Parser\Preset::default()` - default preset, all features

```php
$parser = new FFI\Parser\Parser(preset: Preset::php());
```

Without these presets it is not possible to obtain an unambiguous tree:

```php
// Size of an array of 4 uint8_t elements.
// PHP Analogue: `count([1, 2, 3, 4])`
$ast = (new FFI\Parser\Parser())
    ->parse('int a = sizeof(uint8_t[4]);');

// The size of the value of the 4th variable in `uint8_t` array.
// PHP Analogue: `count($uint8_t[4])`
$ast = (new FFI\Parser\Parser(preset: FFI\Parser\Preset::c23()))
    ->parse('int a = sizeof(uint8_t[4]);');
```

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

## Limitations

Below is a table of features from various compilers that the parser also supports. 
However, other compilers might interpret these differently or fail with an error:

| # | Construct                                       | Example                                     | From                         | Other                                 |
|---|-------------------------------------------------|---------------------------------------------|------------------------------|---------------------------------------|
| 1 | The keywords of the extensions                  | `void f(int * _Nullable);`                  | Clang                        | A parameter `_Nullable`               |
| 1 | Same                                            | `int __ptr64;`                              | Clang with `-fms-extensions` | A variable `__ptr64` (MSVC: an error) |
| 1 | Same                                            | `int __forceinline;`                        | MSVC                         | A variable `__forceinline`            |
| 2 | The escape character                            | `char *s = "\e";`                           | GCC, Clang                   | MSVC: `e`                             |
| 3 | Block pointers                                  | `void (^b)(int);`                           | Clang with `-fblocks`        | An error                              |
| 4 | `__ptrauth`                                     | `void * __ptrauth(1, 0, 0) p;`              | Clang for arm64e             | An error                              |
| 5 | The octal prefix of C2y                         | `int x = 0o17;`                             | GCC 15, Clang 21             | An error                              |
| 6 | GCC attributes in the brackets of an array      | `void f(int a[__attribute__((unused)) 3]);` | GCC                          | An error                              |
| 7 | The last static assertion without its semicolon | `struct S { int a; static_assert(1) };`     | GCC                          | An error                              |
| 8 | `__extension__` before a parameter              | `void f(__extension__ int a);`              | MSVC, PHP FFI                | An error                              |
| 9 | Semicolons among the members                    | `struct S { int a; ; };`                    | GCC, Clang                   | An error                              |

The syntax of the compilers the parser does not accept (yet?):

| #   | Construct                                                    | Example                               | Compiler                  |
|-----|--------------------------------------------------------------|---------------------------------------|---------------------------|
| 1   | Spellings of the keywords outside of the reserved names      | `int f(void) asm("f");`               | GCC, Clang                |
| 1   | Same                                                         | `int _cdecl f(void);`                 | Clang, MSVC               |
| 1   | Same                                                         | `int cdecl f(void);`                  | MSVC                      |
| 1   | Same                                                         | `_inline int f(void);`                | Clang, MSVC               |
| 1   | Same                                                         | `_declspec(dllimport) int x;`         | Clang, MSVC               |
| 2   | `__declspec` with a string literal (SAL annotations)         | `__declspec("SAL_pre") void f(void);` | Clang, MSVC               |
| 3   | `$` in identifiers                                           | `int a$b;`                            | GCC, Clang, MSVC          |
| 4   | Raw string literals                                          | `char *s = R"(abc)";`                 | GCC, Clang                |
| 5   | Obsolete designators                                         | `int a[] = {[1] 5};`                  | GCC, Clang                |
| 5   | Same                                                         | `struct P p = { y: 1 };`              | GCC, Clang                |
| 6   | Extended `asm` at the file scope                             | `__asm__("nop" : );`                  | GCC                       |
| 7   | Old-style function definitions                               | `int f(a) int a; { return a; }`       | GCC, MSVC                 |
| 8   | Named address spaces                                         | `__seg_fs int x;`                     | GCC, Clang                |
| 9   | `_ExtInt`, `__private_extern__`, keyword attributes of SME   | `_ExtInt(8) x;`                       | Clang                     |
| 9.1 | Same                                                         | `__private_extern__ int x;`           | Clang                     |
| 9.2 | Same                                                         | `void f(void) __arm_streaming;`       | Clang                     |
| 10  | Block literals                                               | `int (^b)(void) = ^{ return 1; };`    | Clang                     |
| 11  | `_Countof` with a type name                                  | `int x = _Countof(int[3]);`           | Clang                     |
| 12  | Declarations that declare nothing                            | `const;`                              | GCC, Clang, MSVC, PHP FFI |
| 12  | Same                                                         | `struct S { int; };`                  | GCC, Clang                |
| 13  | Implicit `int`                                               | `static __cdecl f(void);`             | MSVC                      |
| 14  | `__extension__` and qualifiers in any place of a declaration | `int __extension__ x;`                | MSVC                      |
| 14  | Same                                                         | `int a, const f(void);`               | Clang, MSVC               |
| 15  | Incomplete escape sequences and empty character constants    | `char *s = "\x";`                     | PHP FFI                   |
| 15  | Same                                                         | `char *s = "\u00";`                   | MSVC, PHP FFI             |
| 15  | Same                                                         | `char c = '';`                        | PHP FFI                   |
| 16  | Initializers with `=` inside the braces                      | `int x = {=1};`                       | PHP FFI                   |
| 17  | Initializer of a function                                    | `int f(void) = 0;`                    | PHP FFI                   |
| 18  | `#` as a comment in the middle of a line                     | `int x; # comment`                    | PHP FFI                   |
| 19  | A line splice inside a token other than a literal            | `in\` and `t x;` on the next line     | GCC, Clang, MSVC          |

These syntactic constructs are not supported and are extremely rarely used. 
If you need something, please open an issue or a PR:

- **1.** The names are not reserved in C23, `int asm;` and `int _cdecl;` are valid C23.
- **2.** Only `cl /analyze` keeps the SAL annotations, a preprocessed header has none.
- **3.** An implementation-defined character (C23 J.5.3) not found in the headers.
- **4.** A syntax of C++, not of C.
- **5.** Obsolete since GCC 2.5.
- **6.** Operands have no use outside of a function, not found in the headers.
- **7.** Removed from C23 (6.9.2).
- **8.** A keyword of GCC on x86 only, Clang expands it to an attribute the parser reads.
- **9.** A deprecated spelling of `_BitInt`.
- **9.1.** A storage class of Apple, not found in the headers.
- **9.2.** AArch64 SME only.
- **10.** A function body in an expression.
- **11.** An operator of C2y.
- **12.** A constraint violation of C23 (6.7.1), the compilers warn and ignore the declaration.
- **13.** Removed in C99, MSVC warns (C4431).
- **14.** Leniency of MSVC, not a documented extension.
- **15.** A bug of the lexers of MSVC and PHP FFI, GCC, Clang and C23 (6.4.4.5) reject them.
- **16.** A bug of the PHP FFI grammar, its `initializer` rule starts with `=`, so `{=1}` is required and `{1}` rejected, the values are discarded.
- **17.** A bug of PHP FFI, the initializer is discarded unchecked.
- **18.** Conflicts with `#` in the arguments of attributes (C23 6.7.13.2).
- **19.** The preprocessor removes it, a preprocessed header has none.
