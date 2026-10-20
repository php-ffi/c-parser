<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Parser;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use FFI\Parser\Transformer\TransformerRuleInterface;

abstract class RuleTestCase extends TestCase
{
    private const NAMES = 'typedef int T, U; int f, g, x, y, z; ';

    abstract protected static function createRule(): TransformerRuleInterface;

    /**
     * @return list<TransformerRuleInterface<covariant NodeInterface>>
     */
    protected static function createRules(): array
    {
        return [static::createRule()];
    }

    protected static function assertDeclarationIsKept(string $code): void
    {
        self::assertSame(
            NodePrinter::print(\array_last((new Parser(validatorRules: [], transformerRules: []))->parse(self::NAMES . $code))),
            self::transform($code),
        );
    }

    protected static function transform(string $code, bool $withOffsets = false): string
    {
        $declarations = self::transformDeclarations($code);

        if ($withOffsets) {
            return NodePrinter::printWithOffsets(\array_last($declarations));
        }

        return NodePrinter::print(\array_last($declarations));
    }

    /**
     * @return list<RootDeclarationInterface>
     */
    protected static function transformDeclarations(string $code): array
    {
        return (new Parser(validatorRules: [], transformerRules: static::createRules()))->parse(self::NAMES . $code);
    }
}
