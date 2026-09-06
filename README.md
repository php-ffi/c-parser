# FFI Parser

<p align="center">
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/require/php?style=for-the-badge" alt="PHP 8.1+"></a>
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/version?style=for-the-badge" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/v/unstable?style=for-the-badge" alt="Latest Unstable Version"></a>
    <a href="https://packagist.org/packages/ffi/parser"><img src="https://poser.pugx.org/ffi/parser/downloads?style=for-the-badge" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/php-ffi/parser/master/LICENSE"><img src="https://poser.pugx.org/ffi/parser/license?style=for-the-badge" alt="License MIT"></a>
</p>
<p align="center">
    <a href="https://github.com/php-ffi/parser/actions"><img src="https://github.com/php-ffi/parser/workflows/build/badge.svg"></a>
</p>


Parses C headers (the ones you pass to `FFI::cdef()`) into an AST. Useful for 
generating wrappers and stubs or checking a header before loading it.

This implementation of a C-parser based on
[ISO/IEC 9899:2024 - N3220](https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf).

## Installation

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
