<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\SyntaxException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\Node;
use FFI\Parser\Parser;
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

        yield 'declaration without declarators' => ['static const unsigned long;'];
        yield 'structure' => ['struct S { int a : 3; struct { char b; }; };'];
        yield 'enumeration' => ['enum E : unsigned char { A = 1 << 2, B, };'];
        yield 'typedef name without declarators' => ['T;'];
        yield 'members with declarators' => ['struct S { int (*f)(const char *, ...), a[3]; };'];
        yield 'identifier in UTF-8' => ["\xC3\x84;"];

        yield 'static assertion' => ['static_assert(sizeof(T) == 4, "T");'];
        yield 'static assertion without a message' => ['_Static_assert((T)1 + 2);'];
        yield 'static assertions between empty declarations' => [
            ";static_assert(1);\n;static_assert(a ? b : c, \"a\" \"b\");",
        ];
    }

    /**
     * Syntactically invalid headers, along with the offset and the text of
     * the fragment the parser has failed at, empty at the end of input.
     *
     * @return iterable<non-empty-string, array{string, int<0, max>, string}>
     */
    public static function syntaxErrorsDataProvider(): iterable
    {
        yield 'declaration without a semicolon' => ['int', 3, ''];
        yield 'statement keyword' => ["; \n return", 4, 'return'];
        yield 'unclosed structure' => ['struct S {', 10, ''];
        yield 'literal' => ['; 42', 2, '42'];
        yield 'unclosed block comment' => ['; /* comment', 2, '/'];

        yield 'hash not at the beginning of a line' => ['; # x', 2, '#'];
        yield 'octal constant with 8' => ['08', 0, '08'];
        yield 'binary constant with 2' => ['0b2', 0, '0b2'];
        yield 'hexadecimal prefix without digits' => ['0x', 0, '0x'];
        yield 'doubled digit separator' => ["1''0", 0, "1''0"];
        yield 'trailing digit separator' => ["1'", 0, "1'"];
        yield 'invalid integer suffix' => ['1lul', 0, '1lul'];
        yield 'unclosed string' => ['"text', 0, '"text'];
        yield 'unclosed character constant' => ["'a", 0, "'a"];
        yield 'unknown escape sequence' => ['"\\q"', 0, '"\\q"'];

        yield 'static assertion without a semicolon' => ['static_assert(1)', 16, ''];
        yield 'function call in a static assertion' => ['static_assert(f(1));', 15, '('];
        yield 'assignment in a static assertion' => ['static_assert(a = 1);', 16, '='];
        yield 'parameter after the ellipsis' => ['struct S { int f(..., int); };', 20, ','];
    }

    /**
     * Parses a header that is expected to be valid. The invariant of the
     * parser is checked along the way: a header is syntactically valid
     * exactly when it can be parsed.
     *
     * @return list<RootDeclarationInterface>
     */
    protected static function parse(string $code): array
    {
        $parser = new Parser();

        self::assertTrue($parser->validate($code));

        return $parser->parse($code);
    }

    /**
     * Parses a header of a single declaration of the given class.
     *
     * @template TDeclaration of RootDeclarationInterface
     *
     * @param class-string<TDeclaration> $class
     * @return TDeclaration
     */
    protected static function parseSingle(string $code, string $class): RootDeclarationInterface
    {
        $nodes = self::parse($code);

        self::assertCount(1, $nodes);
        self::assertInstanceOf($class, $nodes[0]);

        return $nodes[0];
    }

    /**
     * Checks that a header is rejected at the given fragment, empty at the
     * end of input. The invariant of the parser is checked along the way.
     *
     * @param int<0, max> $offset
     */
    protected static function assertSyntaxError(string $code, int $offset, string $fragment): void
    {
        $parser = new Parser();

        self::assertFalse($parser->validate($code));

        try {
            $parser->parse($code);
        } catch (SyntaxException $e) {
            self::assertSame(
                [$offset, $fragment],
                [$e->offset, \substr($code, $e->offset, $e->length ?? 0)],
            );
            self::assertSame($code, $e->source->getContents());

            return;
        }

        self::fail('SyntaxException has not been thrown');
    }

    /**
     * Lists every node of the tree as its short class name and offset, in
     * the order of the properties, the ones of a base class first.
     *
     * @return list<non-empty-string>
     */
    protected static function listOffsets(mixed $value): array
    {
        if (\is_array($value)) {
            return \array_merge([], ...\array_map(self::listOffsets(...), \array_values($value)));
        }

        if (!$value instanceof Node) {
            return [];
        }

        $result = [(new \ReflectionClass($value))->getShortName() . '@' . $value->offset];

        foreach (\get_object_vars($value) as $property) {
            $result = [...$result, ...self::listOffsets($property)];
        }

        return $result;
    }
}
