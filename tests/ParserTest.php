<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\InternalException;
use FFI\Parser\Exception\ParserException;
use FFI\Parser\Exception\SyntaxException;
use FFI\Parser\Node\Node;
use FFI\Parser\Parser;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Source\FileSource;
use Phplrt\Source\VirtualSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Parser::class)]
final class ParserTest extends TestCase
{
    private const HEADER_FILE = __DIR__ . '/resources/empty.h';

    private const MISSING_FILE = __DIR__ . '/resources/missing.h';

    #[DataProvider('validHeadersDataProvider')]
    public function testValidHeaderIsParsed(string $code): void
    {
        self::assertContainsOnlyInstancesOf(Node::class, (new Parser())->parse($code));
    }

    #[DataProvider('validHeadersDataProvider')]
    public function testValidHeaderIsValidated(string $code): void
    {
        self::assertTrue((new Parser())->validate($code));
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
    public function testSyntaxErrorIsReportedWithItsPosition(string $code, int $offset, int $length): void
    {
        try {
            (new Parser())->parse($code);
        } catch (SyntaxException $e) {
            self::assertSame($offset, $e->offset);
            self::assertSame($length, $e->length);
            self::assertSame($code, $e->source->getContents());

            return;
        }

        self::fail('SyntaxException has not been thrown');
    }

    public function testUnreadableSourceIsReportedByParsing(): void
    {
        $parser = new Parser();

        try {
            $parser->parse(FileSource::createFromPathname(self::MISSING_FILE));
        } catch (InternalException $e) {
            self::assertInstanceOf(SourceExceptionInterface::class, $e->getPrevious());

            return;
        }

        self::fail('InternalException has not been thrown');
    }

    public function testUnreadableSourceIsReportedByValidation(): void
    {
        try {
            (new Parser())->validate(FileSource::createFromPathname(self::MISSING_FILE));
        } catch (InternalException $e) {
            self::assertInstanceOf(SourceExceptionInterface::class, $e->getPrevious());

            return;
        }

        self::fail('InternalException has not been thrown');
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
            $this->expectException(InternalException::class);

            (new Parser())->{$method}('/*' . \str_repeat('x', 10_000) . '*/;');
        } finally {
            \ini_set('pcre.backtrack_limit', (string) $limit);
            \ini_set('pcre.jit', (string) $jit);
        }
    }

    /**
     * The invariant of the parser: a header is syntactically valid exactly
     * when it can be parsed.
     */
    #[DataProvider('headersDataProvider')]
    public function testValidationAgreesWithParsing(string $code): void
    {
        $parser = new Parser();

        try {
            $parser->parse($code);
            $isParsed = true;
        } catch (ParserException) {
            $isParsed = false;
        }

        self::assertSame($isParsed, $parser->validate($code));
    }
}
