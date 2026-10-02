<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Architecture;

use FFI\Parser\Parser;
use FFI\Parser\Transformer;
use FFI\Parser\Traverser;
use FFI\Parser\Validator;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class LibraryArchitectureTest
{
    public function testNodesDoNotDependOnTheParserTheTraverserTheTransformersOrTheValidators(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('FFI\Parser\Node'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::classname(Parser::class),
                Selector::inNamespace('FFI\Parser\Parser'),
                Selector::classname(Traverser::class),
                Selector::inNamespace('FFI\Parser\Traverser'),
                Selector::classname(Transformer::class),
                Selector::inNamespace('FFI\Parser\Transformer'),
                Selector::classname(Validator::class),
                Selector::inNamespace('FFI\Parser\Validator'),
            )
            ->because('the syntax tree knows nothing of the parser, the traverser, the transformers and the validators that use it');
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

    public function testTraverserDependsOnTheNodesOnly(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(Traverser::class),
                Selector::inNamespace('FFI\Parser\Traverser'),
            )
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(Traverser::class),
                Selector::inNamespace('FFI\Parser\Traverser'),
                Selector::inNamespace('FFI\Parser\Node'),
                Selector::inNamespace('FFI\Parser\Exception'),
            )
            ->because('a visitor walks the syntax tree and can be run without the parser');
    }

    public function testTransformersDependOnTheNodesTheTraverserAndTheExceptionsOnly(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(Transformer::class),
                Selector::inNamespace('FFI\Parser\Transformer'),
            )
            ->canOnly()
            ->dependOn()
            ->classes(
                Selector::classname(Transformer::class),
                Selector::inNamespace('FFI\Parser\Transformer'),
                Selector::inNamespace('FFI\Parser\Node'),
                Selector::classname(Traverser::class),
                Selector::inNamespace('FFI\Parser\Traverser'),
                Selector::inNamespace('FFI\Parser\Exception'),
            )
            ->because('a transformer rewrites the syntax tree and can be run without the parser');
    }

    public function testValidatorsDependOnTheNodesTheTraverserAndTheExceptionsOnly(): Rule
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
                Selector::classname(Traverser::class),
                Selector::inNamespace('FFI\Parser\Traverser'),
                Selector::inNamespace('FFI\Parser\Exception'),
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
