<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Exception\ValidationException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Type\BuiltinType\CharType;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Parser;
use FFI\Parser\Parser\ParserExecutor;
use FFI\Parser\Preset;
use FFI\Parser\Preset\Feature;
use FFI\Parser\Transformer;
use FFI\Parser\Transformer\Rule\ParameterNameRule;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;
use FFI\Parser\Validator;
use FFI\Parser\Validator\Rule\Extension\ComplexIntegerTypeRule;
use FFI\Parser\Validator\Rule\TypeSpecifierRule;
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
        yield 'empty attribute declaration' => ['[[]];'];
        yield 'several attribute declarations' => ["[[]]; [[]];\n[[]];[[]];"];
        yield 'line comment' => ["// comment\nint x;"];
        yield 'line comment at the end of input' => ['int x; // comment'];
        yield 'block comment' => ['/* comment */ int x;'];
        yield 'multiline block comment' => ["/*\n * comment\n */\nint x;"];
        yield 'block comment of stars' => ['/***/ /** comment **/ int x;'];
        yield 'line comment inside block comment' => ['/* // */ int x;'];
        yield 'block comment inside line comment' => ["// /* \nint x;"];
        yield 'comments between declarations' => ["int x; /* a */ int y; // b\n int z;"];
        yield 'CRLF line endings' => ["// comment\r\nint x;\r\n"];
        yield 'invalid UTF-8 in comment' => ["/* \xFF\xFE\xC3 */ int x;"];

        yield 'line marker' => ["# 1 \"x.h\" 3\nint x;"];
        yield 'pragma' => ["#pragma pack(1)\nint x;"];
        yield 'define' => ["#define FFI_SCOPE \"x\"\nint x;"];
        yield 'indented directive' => ["  #pragma once\nint x;"];
        yield 'directive between declarations' => ["int x;\n#pragma once\nint y;"];
        yield 'directive in the last line' => ["int x;\n#pragma once"];
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
        yield 'static assertions between declarations' => [
            "int x;static_assert(1);\nint y;static_assert(a ? b : c, \"a\" \"b\");",
        ];
    }

    /**
     * @return iterable<non-empty-string, array{string, int<0, max>, string}>
     */
    public static function syntaxErrorsDataProvider(): iterable
    {
        yield 'declaration without a semicolon' => ['int', 3, ''];
        yield 'statement keyword' => ["int x; \n return", 9, 'return'];
        yield 'unclosed structure' => ['struct S {', 10, ''];
        yield 'literal' => ['int x; 42', 7, '42'];
        yield 'unclosed block comment' => ['int x; /* comment', 7, '/'];

        yield 'hash not at the beginning of a line' => ['int x; # x', 7, '#'];
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
        yield 'string' => ['/* empty */'];
        yield 'SplFileInfo' => [new \SplFileInfo(self::HEADER_FILE)];
        yield 'phplrt source' => [VirtualSource::createFromString('header.h', '/* empty */')];
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
            ->willReturn(VirtualSource::createFromString('header.h', '/* empty */'));

        $parser = new Parser(sources: $factory);

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
        $parser = new Parser(validatorRules: [self::createIntTypeValidator()]);

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

    public function testConstraintOfC23IsCheckedByDefault(): void
    {
        $code = 'static extern int x;';

        self::assertSame(1, \count((new Parser(validatorRules: []))->parse($code)));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid combination of storage-class specifiers `static extern`');

        (new Parser())->parse($code);
    }

    public function testValidatorsAreGivenTheParsedDeclarations(): void
    {
        $validator = self::createRecordingValidator();

        $declarations = (new Parser(validatorRules: [$validator]))->parse('char a; long b;');

        self::assertNotSame([], $validator->contexts);
        self::assertSame($declarations, $validator->contexts[0]->declarations);
    }

    public function testValidatorsAreNotRunBySyntaxCheck(): void
    {
        $validator = self::createRecordingValidator();

        self::assertTrue((new Parser(validatorRules: [$validator]))->validate('int a;'));
        self::assertSame([], $validator->contexts);
    }

    public function testValidatorsAreNotRunOnSyntaxError(): void
    {
        $validator = self::createRecordingValidator();

        try {
            (new Parser(validatorRules: [$validator]))->parse('int a');
        } catch (SyntaxErrorException) {
            self::assertSame([], $validator->contexts);

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }

    public function testTransformersRewriteTheParsedTree(): void
    {
        $declarations = (new Parser(transformerRules: [self::createIntToCharTransformer()]))->parse('int a;');

        self::assertCount(1, $declarations);
        self::assertInstanceOf(VariableDeclaration::class, $declarations[0]);
        self::assertInstanceOf(CharType::class, $declarations[0]->type);
    }

    public function testValidatorsAreGivenTheTransformedTree(): void
    {
        $parser = new Parser(
            validatorRules: [self::createIntTypeValidator()],
            transformerRules: [self::createIntToCharTransformer()],
        );

        self::assertCount(2, $parser->parse("int a;\nint b;"));
    }

    public function testTransformersAreNotRunBySyntaxCheck(): void
    {
        $transformer = self::createIntToCharTransformer();

        self::assertTrue((new Parser(transformerRules: [$transformer]))->validate('int a;'));
        self::assertSame(0, $transformer->visits);
    }

    /**
     * @return iterable<non-empty-string, array{string, string, string}>
     */
    public static function presetTypedefNamesDataProvider(): iterable
    {
        yield 'abstract declarator of a parameter' => [
            'int f(int (size_t));',
            '(function (fn int ((fn int (size_t)))) f)',
            '(function (fn int (int size_t)) f)',
        ];
        yield 'type name of sizeof' => [
            'int a = sizeof(uint8_t[4]);',
            '(variable int a = (sizeof (array uint8_t 4)))',
            '(variable int a = (sizeof (paren ([] uint8_t 4))))',
        ];
    }

    #[DataProvider('presetTypedefNamesDataProvider')]
    public function testTypedefNamesOfThePresetAreDeclaredBeforeTheHeader(
        string $code,
        string $withNames,
        string $withoutNames,
    ): void {
        self::assertSame(
            [$withNames, $withoutNames],
            [
                NodePrinter::print(\array_last((new Parser(preset: new Preset(typedefNames: ['uint8_t', 'size_t'])))
                    ->parse($code))),
                NodePrinter::print(\array_last((new Parser(preset: Preset::c23()))->parse($code))),
            ],
        );
    }

    public function testTypedefNamesOfThePresetAreGivenToTheGivenTransformers(): void
    {
        $parser = new Parser(transformerRules: [new ParameterNameRule()], preset: new Preset(typedefNames: ['T']));

        self::assertSame(
            '(function (fn int ((fn int (T)))) f)',
            NodePrinter::print($parser->parse('int f(int (T));')[0]),
        );
    }

    public function testTypedefNameOfThePresetIsHiddenByTheHeader(): void
    {
        $parser = new Parser(preset: new Preset(typedefNames: ['size_t', 'uint8_t']));

        self::assertSame(
            '(function (fn int (int size_t)) f)',
            NodePrinter::print(\array_last($parser->parse('int size_t; int f(int (size_t));'))),
        );
    }

    public function testFeaturesOfThePresetRemoveTheirRulesFromTheGivenOnes(): void
    {
        $parser = new Parser(
            preset: new Preset([Feature::ComplexIntegers]),
            validatorRules: [new TypeSpecifierRule(), new ComplexIntegerTypeRule()],
        );

        self::assertSame('(variable (_Complex int) x)', NodePrinter::print($parser->parse('_Complex int x;')[0]));
    }

    public function testRulesOfTheFeaturesOfThePresetAreRunWithoutThem(): void
    {
        $parser = new Parser(
            preset: Preset::c23(),
            validatorRules: [new TypeSpecifierRule(), new ComplexIntegerTypeRule()],
        );

        $this->expectException(ValidationException::class);

        $parser->parse('_Complex int x;');
    }

    public function testDisabledExtensionIsRejectedByParseAtItsKeyword(): void
    {
        $code = 'int f(void) __attribute__((noreturn));';
        $parser = new Parser(preset: Preset::c23());

        self::assertTrue($parser->validate($code));

        try {
            $parser->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "GnuAttributes" is not enabled', 12, '__attribute__'],
                [$e->getMessage(), $e->offset, \substr($code, $e->offset, $e->length ?? 0)],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }

    private static function createIntTypeValidator(): ValidatorRuleInterface
    {
        return new class implements ValidatorRuleInterface {
            public function getNodeClasses(): iterable
            {
                return [NodeInterface::class];
            }

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

            public function getNodeClasses(): iterable
            {
                return [NodeInterface::class];
            }

            public function validate(NodeInterface $node, ValidationContext $context): iterable
            {
                $this->contexts[] = $context;

                return [];
            }
        };
    }

    /**
     * @return TransformerRuleInterface&object{visits: int<0, max>}
     */
    private static function createIntToCharTransformer(): TransformerRuleInterface
    {
        return new class implements TransformerRuleInterface {
            /**
             * @var int<0, max>
             */
            public int $visits = 0;

            public function getNodeClasses(): iterable
            {
                return [NodeInterface::class];
            }

            public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
            {
                ++$this->visits;

                return $node instanceof IntType ? new CharType($node->offset) : null;
            }
        };
    }
}
