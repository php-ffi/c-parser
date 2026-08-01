<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Architecture;

use FFI\Parser\Parser;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class LibraryArchitectureTest
{
    public function testNodesDoNotDependOnTheParser(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('FFI\Parser\Node'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::classname(Parser::class),
                Selector::inNamespace('FFI\Parser\Parser'),
                Selector::inNamespace('FFI\Parser\Validator'),
            )
            ->because('the syntax tree is shared by the parser and the validators and knows nothing of them');
    }

    public function testParserInternalsAreUsedByTheParserOnly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::all())
            ->excluding(
                Selector::classname(Parser::class),
                Selector::inNamespace('FFI\Parser\Parser'),
            )
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('FFI\Parser\Parser'))
            ->because('the generated parser is an implementation detail of the public parser');
    }
}
