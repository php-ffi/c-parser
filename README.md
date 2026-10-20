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

### AST Transformers

The parser contains a set of built-in AST transformations. To use your own,
pass it to the constructor:

```php
// override transformers
$parser = new FFI\Parser\Parser(transformers: [
    new MyAstTransformer(),
]);

// or extend existing ones
$parser = new FFI\Parser\Parser(
    transformers: FFI\Parser\Transformer::getDefaultTransformers([
        new MyAstTransformer(),
    ]),
);
```

### AST Validators

The parser checks the AST with validators after the transformations. To use
your own, pass it to the constructor:

```php
// override rules
$parser = new FFI\Parser\Parser(rules: [
    new MyValidatorRule(),
]);

// or extend existing ones
$parser = new FFI\Parser\Parser(
    rules: FFI\Parser\Validator::getDefaultRules([
        new MyValidatorRule(),
    ]),
);
```

### Compiler Presets

A preset lists the typedef names a compiler knows without any header. 

By default the parser knows the names of Clang, GCC, MSVC and PHP FFI. To use 
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
- `FFI\Parser\Preset::empty()` - no additional features
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
$ast = (new FFI\Parser\Parser(preset: FFI\Parser\Preset::empty()))
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

The meaning of some code depends on whether a name is a typedef: `(T)-x` is
a cast or a subtraction. The parser knows only the names declared in the
header itself and the names of the preset, a name from a header that is not
included keeps the default reading: `int f(int (T));` declares a parameter `T`.
