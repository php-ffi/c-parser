<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Exception\ValidationException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Parser;
use FFI\Parser\Parser\ParserExecutor;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Source\FileSource;
use Phplrt\Source\VirtualSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Parser::class)]
#[CoversClass(ParserExecutor::class)]
#[CoversClass(InternalException::class)]
#[CoversClass(SyntaxErrorException::class)]
#[CoversClass(ValidationException::class)]
final class ParserTest extends TestCase
{
    private const HEADER_FILE = __DIR__ . '/resources/empty.h';

    private const MISSING_FILE = __DIR__ . '/resources/missing.h';

    /**
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
        yield 'declarations with declarators' => ['typedef int T; static T a = {[0] = 1}, *f(void);'];
        yield 'identifier in UTF-8' => ["\xC3\x84;"];

        yield 'static assertion' => ['static_assert(sizeof(T) == 4, "T");'];
        yield 'static assertion without a message' => ['_Static_assert((T)1 + 2);'];
        yield 'static assertions between empty declarations' => [
            ";static_assert(1);\n;static_assert(a ? b : c, \"a\" \"b\");",
        ];
    }

    /**
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
        yield 'doubled digit separator' => ["1''0", 0, '1'];
        yield 'trailing digit separator' => ["1'", 0, '1'];
        yield 'number with two dots' => ['static_assert(1.2.3);', 14, '1.2.3'];
        yield 'invalid integer suffix' => ['1lul', 0, '1lul'];
        yield 'unclosed string' => ['"text', 0, '"text'];
        yield 'unclosed character constant' => ["'a", 0, "'a"];
        yield 'unknown escape sequence' => ['"\\q"', 0, '"\\q"'];

        yield 'static assertion without a semicolon' => ['static_assert(1)', 16, ''];
    }

    #[DataProvider('validHeadersDataProvider')]
    public function testValidHeaderIsParsed(string $code): void
    {
        self::assertContainsOnlyInstancesOf(RootDeclarationInterface::class, self::parse($code));
    }

    /**
     * @return iterable<non-empty-string, array{mixed}>
     */
    public static function sourcesDataProvider(): iterable
    {
        yield 'string' => [';'];
        yield 'SplFileInfo' => [new \SplFileInfo(self::HEADER_FILE)];
        yield 'phplrt source' => [VirtualSource::createFromString('header.h', ';')];
    }

    #[DataProvider('sourcesDataProvider')]
    public function testSourceOfEveryKindIsParsed(mixed $source): void
    {
        self::assertSame([], (new Parser())->parse($source));
    }

    public function testStreamResourceIsParsed(): void
    {
        $stream = \fopen(self::HEADER_FILE, 'rb');

        self::assertIsResource($stream);

        try {
            self::assertSame([], (new Parser())->parse($stream));
        } finally {
            \fclose($stream);
        }
    }

    public function testUnsupportedSourceIsReported(): void
    {
        $this->expectException(InternalException::class);

        (new Parser())->parse(42);
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('syntaxErrorsDataProvider')]
    public function testSyntaxErrorIsReportedWithItsPosition(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function methodsDataProvider(): iterable
    {
        yield 'parse' => ['parse'];
        yield 'validate' => ['validate'];
    }

    /**
     * @param non-empty-string $method
     */
    #[DataProvider('methodsDataProvider')]
    public function testUnreadableSourceIsReportedAsInternal(string $method): void
    {
        try {
            (new Parser())->{$method}(FileSource::createFromPathname(self::MISSING_FILE));
        } catch (InternalException $e) {
            self::assertInstanceOf(SourceExceptionInterface::class, $e->getPrevious());

            return;
        }

        self::fail('InternalException has not been thrown');
    }

    /**
     * @param non-empty-string $method
     */
    #[DataProvider('methodsDataProvider')]
    public function testLexerFailureIsReportedAsInternal(string $method): void
    {
        $limit = \ini_get('pcre.backtrack_limit');
        $jit = \ini_get('pcre.jit');

        \ini_set('pcre.backtrack_limit', '100');
        \ini_set('pcre.jit', '0');

        try {
            (new Parser())->{$method}('/*' . \str_repeat('*x', 10_000) . '*/;');
        } catch (InternalException $e) {
            self::assertInstanceOf(LexerRuntimeExceptionInterface::class, $e->getPrevious());

            return;
        } finally {
            \ini_set('pcre.backtrack_limit', (string) $limit);
            \ini_set('pcre.jit', (string) $jit);
        }

        self::fail('InternalException has not been thrown');
    }

    public function testSourcesAreCreatedByTheGivenFactory(): void
    {
        $factory = $this->createMock(SourceFactoryInterface::class);
        $factory->expects(self::exactly(2))
            ->method('create')
            ->with('header')
            ->willReturn(VirtualSource::createFromString('header.h', ';'));

        $parser = new Parser($factory);

        self::assertSame([], $parser->parse('header'));
        self::assertTrue($parser->validate('header'));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, bool}>
     */
    public static function deeplyNestedHeadersDataProvider(): iterable
    {
        $depth = 64;

        yield 'anonymous members' => [
            'struct S { ' . \str_repeat('struct { ', $depth) . 'int a; ' . \str_repeat('}; ', $depth) . '};',
            true,
        ];
        yield 'casts' => ['static_assert(' . \str_repeat('(int)', $depth) . '1);', true];
        yield 'casts without an operand' => ['static_assert(' . \str_repeat('(int)', $depth) . ');', false];
        yield 'alignment specifiers' => [
            'alignas(' . \str_repeat('alignas(', $depth) . '8' . \str_repeat(')', $depth) . ') int;',
            true,
        ];
        yield 'parenthesized declarators' => [
            'struct S { int ' . \str_repeat('(*', $depth) . 'p' . \str_repeat(')', $depth) . '; };',
            true,
        ];
        yield 'parameters' => [
            'struct S { void ' . \str_repeat('(*f)(void ', $depth) . \str_repeat(')', $depth) . '; };',
            true,
        ];
        yield 'abstract parameters' => [
            'struct S { void f(' . \str_repeat('void (*)(', $depth) . 'int' . \str_repeat(')', $depth) . '); };',
            true,
        ];
        yield 'functions returning pointers as parameters' => [
            'struct S { void ' . \str_repeat('f(int (*', $depth) . \str_repeat('))', $depth) . '; };',
            true,
        ];
        yield 'initializer lists' => [
            'int a = ' . \str_repeat('{[0] = ', $depth) . '1' . \str_repeat('}', $depth) . ';',
            true,
        ];
        yield 'compound literals in array sizes' => [
            'int a = ' . \str_repeat('(int[sizeof ', $depth) . '1' . \str_repeat(']){0}', $depth) . ';',
            true,
        ];
        yield 'compound literals' => [
            'int a = ' . \str_repeat('(int[]){', $depth) . '1' . \str_repeat('}', $depth) . ';',
            true,
        ];
        yield 'products that read as type names' => [
            'static_assert(' . \str_repeat('(a * (', $depth) . 'b' . \str_repeat('))', $depth) . ');',
            true,
        ];
    }

    #[DataProvider('deeplyNestedHeadersDataProvider')]
    public function testDeeplyNestedHeaderIsParsedQuickly(string $code, bool $isValid): void
    {
        $start = \microtime(true);

        self::assertSame($isValid, (new Parser())->validate($code));
        self::assertLessThan(1.0, \microtime(true) - $start);
    }

    public function testViolationsAreReportedByValidationException(): void
    {
        $parser = new Parser(rules: [self::createIntTypeValidator()]);

        try {
            $parser->parse("int a;\nint b;");
        } catch (ValidationException $e) {
            self::assertSame('No int (and 1 more)', $e->getMessage());
            self::assertSame([0, 3], [$e->offset, $e->length]);
            self::assertSame([0, 7], \array_map(static fn(Violation $v): int => $v->offset, $e->violations));

            return;
        }

        self::fail('ValidationException has not been thrown');
    }

    public function testValidatorsAreGivenTheParsedDeclarations(): void
    {
        $validator = self::createRecordingValidator();

        $declarations = (new Parser(rules: [$validator]))->parse('char a; long b;');

        self::assertNotSame([], $validator->contexts);
        self::assertSame($declarations, $validator->contexts[0]->declarations);
    }

    public function testValidatorsAreNotRunBySyntaxCheck(): void
    {
        $validator = self::createRecordingValidator();

        self::assertTrue((new Parser(rules: [$validator]))->validate('int a;'));
        self::assertSame([], $validator->contexts);
    }

    public function testValidatorsAreNotRunOnSyntaxError(): void
    {
        $validator = self::createRecordingValidator();

        try {
            (new Parser(rules: [$validator]))->parse('int a');
        } catch (SyntaxErrorException) {
            self::assertSame([], $validator->contexts);

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }

    private static function createIntTypeValidator(): ValidatorRuleInterface
    {
        return new class implements ValidatorRuleInterface {
            public function validate(NodeInterface $node, ValidationContext $context): iterable
            {
                if ($node instanceof IntType) {
                    yield new Violation('No int', $node->offset, 3);
                }
            }
        };
    }

    /**
     * @return ValidatorRuleInterface&object{contexts: list<ValidationContext>}
     */
    private static function createRecordingValidator(): ValidatorRuleInterface
    {
        return new class implements ValidatorRuleInterface {
            /**
             * @var list<ValidationContext>
             */
            public array $contexts = [];

            public function validate(NodeInterface $node, ValidationContext $context): iterable
            {
                $this->contexts[] = $context;

                return [];
            }
        };
    }
}
