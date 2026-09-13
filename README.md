# FFI Parser

<p align="center">
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/require/php?style=for-the-badge" alt="PHP 8.1+"></a>
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/version?style=for-the-badge" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/v/unstable?style=for-the-badge" alt="Latest Unstable Version"></a>
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/downloads?style=for-the-badge" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/php-ffi/parser/master/LICENSE"><img src="https://poser.pugx.org/ffi/parser/license?style=for-the-badge" alt="License MIT"></a>
</p>
<p align="center">
    <a href="https://github.com/php-ffi/parser/actions/workflows/tests.yml"><img src="https://github.com/php-ffi/parser/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
</p>


Parses C headers (the ones you pass to `FFI::cdef()`) into an AST. Useful for 
generating wrappers and stubs or checking a header before loading it.

This implementation of a C-parser based on
[ISO/IEC 9899:2024 - N3220](https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf).

## Requirements

- PHP ^8.1

## Installation

Library is available as a Composer repository and can be installed using the
following command in the root of your project.

```sh
$ composer require ffi/parser
```

## Usage

```php
$parser = new FFI\Parser\Parser();

// Parse file
$declarations = $parser->parse(new SplFileInfo(__DIR__ . '/header.h'));
// Parse string
$declarations = $parser->parse('void move(int dx, int dy);');

echo $declarations[0]->name->name; // "move"
```

An invalid header throws `FFI\Parser\Exception\ParserException`, which points
to the error in the source.

### The syntax tree

`parse()` returns the declarations of the header in the order they are
written, one for each declarator: `int a, *b;` gives two of them. Every node
is immutable and knows its byte offset in the source (`$node->offset`).

| Node (`FFI\Parser\Node\Declaration\...`)                  | Example                      |
|-----------------------------------------------------------|------------------------------|
| `SpecifiedDeclaration\DeclaratorDeclaration\FunctionDeclaration` | `int printf(const char *, ...);` |
| `SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition`  | `static inline int f(void) { ... }` |
| `SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration` | `extern int errno;`          |
| `SpecifiedDeclaration\DeclaratorDeclaration\TypeDefDeclaration`  | `typedef struct S S;`        |
| `SpecifiedDeclaration\TagDeclaration`                     | `struct S { int a; };`       |
| `SpecifiedDeclaration\TypeDeclaration`                    | `int;`                       |
| `StaticAssertDeclaration`                                 | `static_assert(sizeof(int) == 4);` |
| `AttributeDeclaration`                                    | `[[deprecated]];`            |

A declaration with a declarator has a `name`, a `type` and the `specifiers`
that are not a part of the type (`extern`, `static`, attributes). Types
(`FFI\Parser\Node\Type\...`) are nested the way C reads them: `int *a[3]` is
an `ArrayType` of a `PointerType` to an `IntType`.

```php
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDeclaration;

foreach ($parser->parse('int printf(const char *format, ...);') as $declaration) {
    if ($declaration instanceof FunctionDeclaration) {
        foreach ($declaration->type->parameters as $parameter) {
            echo $parameter->name?->name; // "format"
        }

        var_dump($declaration->type->isVariadic); // true
    }
}
```

### Errors

Everything the parser throws is a `FFI\Parser\Exception\ParserException`:

- `SyntaxErrorException` - the header is not syntactically valid;
- `ValidationException` - a validator rejected the tree, the reasons are in
  `$violations`;
- `InternalException` - the source cannot be read or analyzed.

The first two point to the error: `$offset` and `$length` in bytes and the
`$source`, and cast to a string they print the message with a snippet of the
source.

```php
try {
    $parser->parse('int x');
} catch (FFI\Parser\Exception\SyntaxErrorException $e) {
    echo $e->getMessage(); // Syntax error, unexpected end of input in the declaration, ";" expected
    echo $e->offset;       // 5
}
```

### Validation

`validate()` only checks the syntax, without building the tree and without
the validators, and returns a `bool`. `parse()` runs the validators over the
tree it builds. A validator implements `FFI\Parser\Validator\ValidatorInterface`,
gets every node of the tree once and returns the `Violation`s it finds:

```php
$parser = new FFI\Parser\Parser(validators: [new MyValidator()]);
```

## Limitations

The parser reads C23 with the extensions of GCC, Clang and MSVC, after the
preprocessor (`gcc -E`). Directives (`#pragma`, `#define` of `gcc -dD`, line
markers) are skipped, macros are not expanded.

- **No table of typedef names.** Whether an identifier is a type is decided
  by the syntax alone:
  - without another type specifier an identifier is a typedef name:
    `static x;` is a declaration of the type `x` without a declarator, and
    `static x[3];` with an implicit `int` is a syntax error;
  - `(T)-x`, `(T)+x`, `(T)*x` and `(T)&x` with a single identifier are
    binary expressions, `(T)(x)` and `(T)~x` are casts;
  - a type name that reads as an expression is an expression:
    `sizeof(T[3])` is the size of an element, `typeof(T())` is the type of a
    call.
- **Preprocessed input.** A backslash at the end of a line continues a
  directive only, not a comment or a token. Lines that end with a lone CR are
  not supported. Old-style (K&R) function definitions are not supported.
- **Function bodies** are kept as text (`FunctionBody`), their statements
  are not parsed.
