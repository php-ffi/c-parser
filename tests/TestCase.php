<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\Node;
use FFI\Parser\Parser;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return list<RootDeclarationInterface>
     */
    protected static function parse(string $code): array
    {
        $parser = new Parser(validatorRules: []);

        self::assertTrue($parser->validate($code));

        return $parser->parse($code);
    }

    /**
     * @template TDeclaration of RootDeclarationInterface
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
     * @param int<0, max> $offset
     */
    protected static function assertSyntaxError(string $code, int $offset, string $fragment): void
    {
        $parser = new Parser();

        self::assertFalse($parser->validate($code));

        try {
            $parser->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                [$offset, $fragment],
                [$e->offset, \substr($code, $e->offset, $e->length ?? 0)],
            );
            self::assertSame($code, $e->source->getContents());

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }

    /**
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
