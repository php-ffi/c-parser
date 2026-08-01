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
    }

    /**
     * Syntactically invalid headers, along with the offset and the size of
     * the fragment the parser has failed at.
     *
     * @return iterable<non-empty-string, array{string, int<0, max>, int<0, max>}>
     */
    public static function syntaxErrorsDataProvider(): iterable
    {
        yield 'unknown word' => ['int', 0, 3];
        yield 'unknown word after declaration' => ["; \n x", 4, 1];
        yield 'unclosed block comment' => ['; /* comment', 2, 2];
        yield 'directive' => ['#pragma once', 0, 7];
        yield 'non-ASCII bytes' => ["\xC3\x84", 0, 2];
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
