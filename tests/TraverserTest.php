<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Exception\ChildrenSkipException;
use FFI\Parser\Exception\NodeRemoveException;
use FFI\Parser\Exception\NodeReplaceException;
use FFI\Parser\Exception\TraverserException;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Node\Type\RecordType\StructType;
use FFI\Parser\Traverser;
use FFI\Parser\Traverser\Action;
use FFI\Parser\Traverser\Context;
use FFI\Parser\Traverser\VisitorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Traverser::class)]
#[CoversClass(Context::class)]
#[CoversClass(Action::class)]
#[CoversClass(NodeRemoveException::class)]
#[CoversClass(NodeReplaceException::class)]
#[CoversClass(ChildrenSkipException::class)]
final class TraverserTest extends TestCase
{
    public function testNodeIsEnteredBeforeAndLeftAfterItsChildren(): void
    {
        $visits = [];

        (new Traverser([self::createRecordingVisitor($visits)]))->traverse(self::parse('int a = b;'));

        self::assertSame(
            [
                '> VariableDeclaration@0',
                '  > SpecifierList@0',
                '  < SpecifierList@0',
                '  > Identifier@4',
                '  < Identifier@4',
                '  > IdentifierExpression@8',
                '    > Identifier@8',
                '    < Identifier@8',
                '  < IdentifierExpression@8',
                '  > IntType@0',
                '  < IntType@0',
                '< VariableDeclaration@0',
            ],
            $visits,
        );
    }

    public function testSharedNodeIsVisitedOnce(): void
    {
        $visits = [];

        (new Traverser([self::createRecordingVisitor($visits)]))->traverse(self::parse('struct S {} x, *y;'));

        self::assertCount(
            1,
            \array_filter($visits, static fn(string $visit): bool => \str_contains($visit, '> StructType')),
        );
    }

    public function testReplacementTakesThePlaceOfSharedNodeInEveryParent(): void
    {
        $declarations = (new Traverser([
            self::createVisitor(
                leave: static fn(NodeInterface $node): ?NodeInterface => $node instanceof StructType
                    ? new NamedType(new Identifier('S', 7), 0)
                    : null,
            ),
        ]))->traverse(self::parse('struct S {} x, *y;'));

        self::assertSame(
            ['(variable S x)', '(variable (ptr S) y)'],
            \array_map(NodePrinter::print(...), $declarations),
        );
    }

    public function testChildrenOfReplacementGivenOnEnterAreWalked(): void
    {
        $visits = [];

        $declarations = (new Traverser([
            self::createVisitor(
                enter: static fn(NodeInterface $node): ?NodeInterface => $node instanceof IdentifierExpression
                    ? new IdentifierExpression(new Identifier('c', 8), 8)
                    : null,
            ),
            self::createRecordingVisitor($visits),
        ]))->traverse(self::parse('int a = b;'));

        self::assertSame('(variable int a = c)', NodePrinter::print($declarations[0]));
        self::assertContains('    > Identifier@8', $visits);
    }

    public function testReplacementGivenOnLeaveIsNotWalked(): void
    {
        $visits = [];
        $replacement = new IdentifierExpression(new Identifier('c', 20), 20);

        $declarations = (new Traverser([
            self::createRecordingVisitor($visits),
            self::createVisitor(
                leave: static fn(NodeInterface $node): ?NodeInterface => $node instanceof IdentifierExpression
                    ? $replacement
                    : null,
            ),
        ]))->traverse(self::parse('int a = b;'));

        self::assertSame('(variable int a = c)', NodePrinter::print($declarations[0]));
        self::assertNotContains('    > Identifier@20', $visits);
    }

    public function testNodeWrappedOnEnterIsNotVisitedAgain(): void
    {
        $visits = [];

        $declarations = (new Traverser([
            self::createVisitor(
                enter: static fn(NodeInterface $node): ?NodeInterface => $node instanceof IdentifierExpression
                    ? new ParenExpression($node, 8)
                    : null,
            ),
            self::createRecordingVisitor($visits),
        ]))->traverse(self::parse('int a = b;'));

        self::assertSame('(variable int a = (paren b))', NodePrinter::print($declarations[0]));
        self::assertSame(['  > ParenExpression@8', '  < ParenExpression@8'], \array_slice($visits, 5, 2));
    }

    public function testVisitorIsGivenTheNodeThePreviousOneReturned(): void
    {
        $replacement = new IntType(0);
        $given = null;

        (new Traverser([
            self::createVisitor(
                enter: static fn(NodeInterface $node): ?NodeInterface => $node instanceof IntType
                    ? $replacement
                    : null,
            ),
            self::createVisitor(enter: static function (NodeInterface $node) use (&$given): ?NodeInterface {
                if ($node instanceof IntType) {
                    $given = $node;
                }

                return null;
            }),
        ]))->traverse(self::parse('int a;'));

        self::assertSame($replacement, $given);
    }

    public function testRemovedNodeLeavesTheList(): void
    {
        $declarations = (new Traverser([
            self::createVisitor(
                enter: static fn(NodeInterface $node): ?Action => $node instanceof MemberDeclaration
                    && $node->name?->value === 'a'
                    ? Action::Remove
                    : null,
            ),
        ]))->traverse(self::parse('struct S { int a; int b; } x;'));

        self::assertSame('(variable struct S { int b } x)', NodePrinter::print($declarations[0]));
    }

    public function testRemovedRootDeclarationLeavesTheResult(): void
    {
        $declarations = self::parse('int a; int b;');

        $result = (new Traverser([
            self::createVisitor(
                leave: static fn(NodeInterface $node): ?Action => $node === $declarations[0]
                    ? Action::Remove
                    : null,
            ),
        ]))->traverse($declarations);

        self::assertSame([$declarations[1]], $result);
    }

    public function testSkippedChildrenAreNotWalked(): void
    {
        $visits = [];

        (new Traverser([
            self::createVisitor(
                enter: static fn(NodeInterface $node): ?Action => $node instanceof IdentifierExpression
                    ? Action::SkipChildren
                    : null,
            ),
            self::createRecordingVisitor($visits),
        ]))->traverse(self::parse('int a = b;'));

        self::assertSame(['  > IdentifierExpression@8', '  < IdentifierExpression@8'], \array_slice($visits, 5, 2));
    }

    public function testContextHoldsTheParentsAndTheDeclarations(): void
    {
        $declarations = self::parse('int x; struct S { int a; } y;');
        $parents = [];

        (new Traverser([
            self::createVisitor(enter: static function (
                NodeInterface $node,
                Context $context,
            ) use ($declarations, &$parents): ?NodeInterface {
                self::assertSame($declarations, $context->declarations);

                if ($node instanceof IntType && $node->offset === 18) {
                    $parents = \array_map(
                        static fn(NodeInterface $parent): string => (new \ReflectionClass($parent))->getShortName(),
                        $context->parents,
                    );
                }

                return null;
            }),
        ]))->traverse($declarations);

        self::assertSame(['VariableDeclaration', 'StructType', 'MemberDeclarationList', 'MemberDeclaration'], $parents);
    }

    public function testDeclarationsWithoutVisitorsAreReturnedAsGiven(): void
    {
        $declarations = self::parse('int a;');

        self::assertSame($declarations, (new Traverser())->traverse($declarations));
    }

    /**
     * @return iterable<non-empty-string, array{VisitorInterface, class-string<TraverserException>, non-empty-string}>
     */
    public static function brokenTreeDataProvider(): iterable
    {
        yield 'node removed from a property that is not a list' => [
            self::createVisitor(
                enter: static fn(NodeInterface $node): ?Action => $node instanceof IntType
                    ? Action::Remove
                    : null,
            ),
            NodeRemoveException::class,
            'The node in the property "type" of ' . VariableDeclaration::class
                . ' cannot be removed by a visitor, the property is not a list',
        ];

        yield 'node replaced with a node the property does not accept' => [
            self::createVisitor(
                leave: static fn(NodeInterface $node): ?NodeInterface => $node instanceof Identifier
                    ? new IntType(0)
                    : null,
            ),
            NodeReplaceException::class,
            'The property "name" of ' . VariableDeclaration::class . ' cannot hold the value given by a visitor',
        ];

        yield 'root declaration replaced with a node that is not a root declaration' => [
            self::createVisitor(
                leave: static fn(NodeInterface $node, Context $context): ?NodeInterface => $context->parents === []
                    ? new IntType(0)
                    : null,
            ),
            NodeReplaceException::class,
            'The root declaration ' . VariableDeclaration::class . ' cannot be replaced by a visitor with '
                . IntType::class . ', which is not a root declaration',
        ];

        yield 'children skipped on leave' => [
            self::createVisitor(leave: static fn(): Action => Action::SkipChildren),
            ChildrenSkipException::class,
            'The children of ' . SpecifierList::class
                . ' cannot be skipped by a visitor that leaves the node, they are already walked',
        ];
    }

    /**
     * @param class-string<TraverserException> $exception
     * @param non-empty-string $message
     */
    #[DataProvider('brokenTreeDataProvider')]
    public function testVisitorThatBreaksTheTreeIsReported(
        VisitorInterface $visitor,
        string $exception,
        string $message,
    ): void {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        (new Traverser([$visitor]))->traverse(self::parse('int a;'));
    }

    /**
     * @param list<non-empty-string> $visits
     */
    private static function createRecordingVisitor(array &$visits): VisitorInterface
    {
        $record = static function (string $prefix) use (&$visits): \Closure {
            return static function (NodeInterface $node, Context $context) use ($prefix, &$visits): ?NodeInterface {
                $visits[] = \str_repeat('  ', \count($context->parents))
                    . $prefix . ' ' . (new \ReflectionClass($node))->getShortName() . '@' . $node->offset;

                return null;
            };
        };

        return self::createVisitor($record('>'), $record('<'));
    }

    /**
     * @param (\Closure(NodeInterface, Context): (NodeInterface|Action|null))|null $enter
     * @param (\Closure(NodeInterface, Context): (NodeInterface|Action|null))|null $leave
     */
    private static function createVisitor(?\Closure $enter = null, ?\Closure $leave = null): VisitorInterface
    {
        return new class ($enter, $leave) implements VisitorInterface {
            public function __construct(
                /**
                 * @var (\Closure(NodeInterface, Context): (NodeInterface|Action|null))|null
                 */
                private readonly ?\Closure $onEnter,
                /**
                 * @var (\Closure(NodeInterface, Context): (NodeInterface|Action|null))|null
                 */
                private readonly ?\Closure $onLeave,
            ) {}

            public function enter(NodeInterface $node, Context $context): NodeInterface|Action|null
            {
                return $this->onEnter === null ? null : ($this->onEnter)($node, $context);
            }

            public function leave(NodeInterface $node, Context $context): NodeInterface|Action|null
            {
                return $this->onLeave === null ? null : ($this->onLeave)($node, $context);
            }
        };
    }
}
