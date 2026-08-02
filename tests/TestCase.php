<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Syntactically valid headers.
     *
     * @return iterable<non-empty-string, array{string}>
     */
    public static function validHeadersDataProvider(): iterable
    {
        yield 'empty input' => [''];
        yield 'whitespace only' => [" \t\r\n\f\v"];
        yield 'empty declaration' => [';'];
        yield 'several empty declarations' => ["; ;\n;;"];
        yield 'line comment' => ["// comment\n;"];
        yield 'line comment at the end of input' => ['; // comment'];
        yield 'block comment' => ['/* comment */ ;'];
        yield 'multiline block comment' => ["/*\n * comment\n */\n;"];
        yield 'block comment of stars' => ['/***/ /** comment **/ ;'];
        yield 'line comment inside block comment' => ['/* // */ ;'];
        yield 'block comment inside line comment' => ["// /* \n;"];
        yield 'comments between declarations' => ["; /* a */ ; // b\n ;"];
        yield 'CRLF line endings' => ["// comment\r\n;\r\n"];
        yield 'invalid UTF-8 in comment' => ["/* \xFF\xFE\xC3 */ ;"];

        yield 'line marker' => ["# 1 \"x.h\" 3\n;"];
        yield 'pragma' => ["#pragma pack(1)\n;"];
        yield 'define' => ["#define FFI_SCOPE \"x\"\n;"];
        yield 'indented directive' => ["  #pragma once\n;"];
        yield 'directive between declarations' => [";\n#pragma once\n;"];
        yield 'directive in the last line' => [";\n#pragma once"];
        yield 'directive only' => ['#pragma once'];

        yield 'static assertion' => ['static_assert(sizeof(T) == 4, "T");'];
        yield 'static assertion without a message' => ['_Static_assert((T)1 + 2);'];
        yield 'static assertions between empty declarations' => [
            ";static_assert(1);\n;static_assert(a ? b : c, \"a\" \"b\");",
        ];
    }

    /**
     * Syntactically invalid headers, along with the offset and the size of
     * the fragment the parser has failed at.
     *
     * @return iterable<non-empty-string, array{string, int<0, max>, int<0, max>}>
     */
    public static function syntaxErrorsDataProvider(): iterable
    {
        yield 'keyword' => ['int', 0, 3];
        yield 'identifier after declaration' => ["; \n x", 4, 1];
        yield 'identifier in UTF-8' => ["\xC3\x84", 0, 2];
        yield 'literal' => ['; 42', 2, 2];
        yield 'unclosed block comment' => ['; /* comment', 2, 1];

        yield 'hash not at the beginning of a line' => ['; # x', 2, 1];
        yield 'octal constant with 8' => ['08', 0, 2];
        yield 'binary constant with 2' => ['0b2', 0, 3];
        yield 'hexadecimal prefix without digits' => ['0x', 0, 2];
        yield 'doubled digit separator' => ["1''0", 0, 4];
        yield 'trailing digit separator' => ["1'", 0, 2];
        yield 'invalid integer suffix' => ['1lul', 0, 4];
        yield 'unclosed string' => ['"text', 0, 5];
        yield 'unclosed character constant' => ["'a", 0, 2];
        yield 'unknown escape sequence' => ['"\\q"', 0, 4];

        yield 'static assertion without a semicolon' => ['static_assert(1)', 16, 0];
        yield 'function call in a static assertion' => ['static_assert(f(1));', 15, 1];
        yield 'assignment in a static assertion' => ['static_assert(a = 1);', 16, 1];
    }

    /**
     * Every header of the other data providers, valid or not.
     *
     * @return iterable<non-empty-string, array{string}>
     */
    public static function headersDataProvider(): iterable
    {
        foreach (self::validHeadersDataProvider() as $name => [$code]) {
            yield 'valid: ' . $name => [$code];
        }

        foreach (self::syntaxErrorsDataProvider() as $name => [$code]) {
            yield 'invalid: ' . $name => [$code];
        }
    }
}
