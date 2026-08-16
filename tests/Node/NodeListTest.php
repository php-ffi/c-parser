<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node;

use FFI\Parser\Node\NodeList;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\ExternSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\StaticSpecifier;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeList::class)]
final class NodeListTest extends TestCase
{
    public function testListGivesItsNodesInOrder(): void
    {
        $static = new StaticSpecifier(0);
        $extern = new ExternSpecifier(7);
        $list = new SpecifierList([$static, $extern]);

        self::assertCount(2, $list);
        self::assertSame([$static, $extern], \iterator_to_array($list));
        self::assertSame($static, $list[0]);
        self::assertSame($extern, $list[1]);
        self::assertTrue(isset($list[1]));
        self::assertFalse(isset($list[2]));
        self::assertNull($list[2]);
    }

    public function testListFindsTheIndexOfItsNode(): void
    {
        $static = new StaticSpecifier(0);
        $extern = new ExternSpecifier(7);
        $list = new SpecifierList([$static, $extern]);

        self::assertSame(1, $list->findIndex($extern));
        self::assertNull($list->findIndex(new ExternSpecifier(7)));
    }

    public function testListGivesItsFirstAndLastNodes(): void
    {
        $static = new StaticSpecifier(0);
        $extern = new ExternSpecifier(7);
        $list = new SpecifierList([$static, $extern]);

        self::assertSame($static, $list->first());
        self::assertSame($extern, $list->last());
        self::assertNull((new SpecifierList([]))->first());
        self::assertNull((new SpecifierList([]))->last());
    }

    public function testListCannotBeModified(): void
    {
        $list = new SpecifierList([new StaticSpecifier(0)]);

        $this->expectException(\RuntimeException::class);

        $list[0] = new ExternSpecifier(7);
    }

    public function testNodeCannotBeRemovedFromList(): void
    {
        $list = new SpecifierList([new StaticSpecifier(0)]);

        $this->expectException(\RuntimeException::class);

        unset($list[0]);
    }
}
