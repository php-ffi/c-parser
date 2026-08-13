<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\SyntaxException;
use FFI\Parser\Node\Declaration\Declaration;
use FFI\Parser\Parser;
use FFI\Parser\Parser\ParserExecutor;
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
#[CoversClass(SyntaxException::class)]
final class ParserTest extends TestCase
{
    private const HEADER_FILE = __DIR__ . '/resources/empty.h';

    private const MISSING_FILE = __DIR__ . '/resources/missing.h';

    #[DataProvider('validHeadersDataProvider')]
    public function testValidHeaderIsParsed(string $code): void
    {
        self::assertContainsOnlyInstancesOf(Declaration::class, self::parse($code));
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

    #[DataProvider('syntaxErrorsDataProvider')]
    /**
     * @param int<0, max> $offset
     */
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
     * A failure of the lexer that is not a defect of the code, such as a
     * limit of PCRE, leaves the parser as an {@see InternalException}.
     *
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
        yield 'products that read as type names' => [
            'static_assert(' . \str_repeat('(a * (', $depth) . 'b' . \str_repeat('))', $depth) . ');',
            true,
        ];
    }

    /**
     * Alternatives of the grammar that share their start parse it once
     * again, which makes the time grow exponentially with the depth of the
     * nesting. A deep header must still be parsed at once.
     */
    #[DataProvider('deeplyNestedHeadersDataProvider')]
    public function testDeepNestingIsParsedInLinearTime(string $code, bool $isValid): void
    {
        $start = \microtime(true);

        self::assertSame($isValid, (new Parser())->validate($code));
        self::assertLessThan(1.0, \microtime(true) - $start);
    }
}
