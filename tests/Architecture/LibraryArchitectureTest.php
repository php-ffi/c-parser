<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Architecture;

use FFI\Parser\Parser;
use FFI\Parser\Validator;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class LibraryArchitectureTest
{
    public function testNodesDoNotDependOnTheParserOrTheValidators(): Rule
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

    public function testValidatorsDependOnTheNodesOnly(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(Validator::class),
                Selector::inNamespace('FFI\Parser\Validator'),
            )
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(Validator::class),
                Selector::inNamespace('FFI\Parser\Validator'),
                Selector::inNamespace('FFI\Parser\Node'),
            )
            ->because('a validator checks the syntax tree and can be run without the parser');
    }

    public function testLibraryDoesNotDependOnTheDevelopmentPackages(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('FFI\Parser'))
            ->excluding(Selector::inNamespace('FFI\Parser\Tests'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('Phplrt\Compiler'),
                Selector::inNamespace('PHPUnit'),
                Selector::inNamespace('PHPat'),
                Selector::inNamespace('PhpCsFixer'),
                Selector::inNamespace('Symfony\Component\VarDumper'),
            )
            ->because('the grammar is compiled at development time, the library needs the runtime packages only');
    }

    public function testLiteralInternalsAreUsedByTheLiteralsOnly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::all())
            ->excluding(Selector::inNamespace('FFI\Parser\Node\Literal'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::classname('/^FFI\\\\Parser\\\\Node\\\\Literal\\\\IntLiteral\\\\\w*BaseConverter/', true),
                Selector::classname('/^FFI\\\\Parser\\\\Node\\\\Literal\\\\\w+\\\\\w+Parser(\\\\|$)/', true),
            )
            ->because('the parsers and the base converters only compute values of literals');
    }
}
