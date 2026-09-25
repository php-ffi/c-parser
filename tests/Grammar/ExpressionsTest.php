<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\Declaration\StaticAssertDeclaration;
use FFI\Parser\Node\Expression\Expression;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Expression\SizeOfTypeExpression;
use FFI\Parser\Node\Literal\BoolLiteral;
use FFI\Parser\Node\Literal\CharLiteral;
use FFI\Parser\Node\Literal\FloatLiteral\DecFloatLiteral;
use FFI\Parser\Node\Literal\FloatLiteral\HexFloatLiteral;
use FFI\Parser\Node\Literal\IntLiteral\BinIntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\DecIntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\HexIntLiteral;
use FFI\Parser\Node\Literal\IntLiteral\OctIntLiteral;
use FFI\Parser\Node\Literal\Literal;
use FFI\Parser\Node\Literal\NullptrLiteral;
use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class ExpressionsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function expressionsDataProvider(): iterable
    {
        yield 'identifier' => ['a', 'a@14'];
        yield 'parenthesized' => ['(a)', '(paren a@15)@14'];
        yield 'operation on a parenthesized operand starts at the parenthesis' => [
            '(a) + b',
            '(+ (paren a@15)@14 b@20)@14',
        ];
        yield 'conditional on a parenthesized operand' => ['(a) ? b : c', '(? (paren a@15)@14 b@20 c@24)@14'];
        yield 'integer constant' => ['0x1Fu', '0x1Fu@14'];
        yield 'floating constant' => ['1.5f', '1.5f@14'];
        yield 'character constant' => ["'a'", "'a'@14"];
        yield 'string literal' => ['"a"', '"a"@14'];
        yield 'adjacent string literals' => ['"a" L"b"', '(concat "a"@14 L"b"@18)@14'];
        yield 'three adjacent string literals' => ['"a" "b" "c"', '(concat "a"@14 "b"@18 "c"@22)@14'];
        yield 'cast of adjacent string literals' => ['(T)"a" "b"', '(cast T@15 (concat "a"@17 "b"@21)@17)@14'];
        yield 'sizeof of adjacent string literals' => ['sizeof "a" "b"', '(sizeof (concat "a"@21 "b"@25)@21)@14'];
        yield 'true' => ['true', 'true@14'];
        yield 'false' => ['false', 'false@14'];
        yield 'nullptr' => ['nullptr', 'nullptr@14'];
        yield 'imaginary constant' => ['2.0i', '2.0i@14'];

        yield 'left associativity' => ['1 - 2 - 3', '(- (- 1@14 2@18)@14 3@22)@14'];
        yield 'multiplication binds tighter' => ['1 + 2 * 3', '(+ 1@14 (* 2@18 3@22)@18)@14'];
        yield 'parentheses override precedence' => ['(1 + 2) * 3', '(* (paren (+ 1@15 2@19)@15)@14 3@24)@14'];
        yield 'unary binds tighter' => ['-x * y', '(* (-u x@15)@14 y@19)@14'];
        yield 'logical and binds tighter' => ['a || b && c', '(|| a@14 (&& b@19 c@24)@19)@14'];
        yield 'equality binds tighter than bitwise and' => ['a & b == c', '(& a@14 (== b@18 c@23)@18)@14'];
        yield 'shift binds looser than addition' => ['a << b + c', '(<< a@14 (+ b@19 c@23)@19)@14'];
        yield 'every level' => [
            'a || b && c | d ^ e & f == g < h << i + j * k',
            '(|| a@14 (&& b@19 (| c@24 (^ d@28 (& e@32 (== f@36 (< g@41 (<< h@45 (+ i@50 (* j@54 k@58)@54)@50)@45)'
                . '@41)@36)@32)@28)@24)@19)@14',
        ];
        yield 'conditional is right associative' => ['a ? b : c ? d : e', '(? a@14 b@18 (? c@22 d@26 e@30)@22)@14'];
        yield 'conditional binds looser than logical or' => ['a || b ? c : d', '(? (|| a@14 b@19)@14 c@23 d@27)@14'];
        yield 'comma between ? and :' => ['a ? b, c : d', '(? a@14 (, b@18 c@21)@18 d@25)@14'];
        yield 'comma in parentheses' => ['(a, b, c)', '(paren (, (, a@15 b@18)@15 c@21)@15)@14'];

        yield 'nested unary operators' => ['-~!x', '(-u (~u (!u x@17)@16)@15)@14'];
        yield 'address of an element' => ['&arr[2]', '(&u ([] arr@15 2@19)@15)@14'];
        yield 'dereference of a member' => ['*p->q', '(*u (-> p@15 q@18)@15)@14'];

        yield 'cast of a name' => ['(T)x', '(cast T@15 x@17)@14'];
        yield 'cast of a constant' => ['(T)1', '(cast T@15 1@17)@14'];
        yield 'cast of a parenthesized operand' => ['(T)(x)', '(cast T@15 (paren x@18)@17)@14'];
        yield 'cast of a negation' => ['(T)~x', '(cast T@15 (~u x@18)@17)@14'];
        yield 'cast of sizeof' => ['(T)sizeof x', '(cast T@15 (sizeof x@24)@17)@14'];
        yield 'cast of alignof' => ['(T)alignof(U)', '(cast T@15 (alignof U@25)@17)@14'];
        yield 'cast of a logical negation' => ['(T)!x', '(cast T@15 (!u x@18)@17)@14'];
        yield 'cast of a floating constant' => ['(T)1.5', '(cast T@15 1.5@17)@14'];
        yield 'cast of a character constant' => ["(T)'a'", "(cast T@15 'a'@17)@14"];
        yield 'cast of a string literal' => ['(T)"a"', '(cast T@15 "a"@17)@14'];
        yield 'cast of true' => ['(T)true', '(cast T@15 true@17)@14'];
        yield 'cast of false' => ['(T)false', '(cast T@15 false@17)@14'];
        yield 'cast of nullptr' => ['(T)nullptr', '(cast T@15 nullptr@17)@14'];
        yield 'plus after parentheses is an addition' => ['(T)+x', '(+ (paren T@15)@14 x@18)@14'];
        yield 'ampersand after parentheses is a bitwise and' => ['(T)&x', '(& (paren T@15)@14 x@18)@14'];
        yield 'nested casts' => ['(A)(B)x', '(cast A@15 (cast B@18 x@20)@17)@14'];
        yield 'minus after parentheses is a subtraction' => ['(T)-x', '(- (paren T@15)@14 x@18)@14'];
        yield 'asterisk after parentheses is a multiplication' => ['(T)*p', '(* (paren T@15)@14 p@18)@14'];
        yield 'subscript after parentheses' => ['(x)[0]', '([] (paren x@15)@14 0@18)@14'];
        yield 'member after parentheses' => ['(x).a', '(. (paren x@15)@14 a@18)@14'];
        yield 'minus after a keyword type is a cast' => ['(int)-1', '(cast int@15 (-u 1@20)@19)@14'];
        yield 'octal zero after a typedef name is a cast' => ['(T)0', '(cast T@15 0@17)@14'];
        yield 'hexadecimal constant after a typedef name is a cast' => ['(T)0x1', '(cast T@15 0x1@17)@14'];
        yield 'sign after a hexadecimal constant with a space' => ['0xE +1', '(+ 0xE@14 1@19)@14'];
        yield 'cast to a modified type' => ['(unsigned long)x', '(cast (unsigned long _)@15 x@29)@14'];
        yield 'minus after a qualified typedef name is a cast' => [
            '(const T)-x',
            '(cast [const T@21]@15 (-u x@24)@23)@14',
        ];
        yield 'sizeof of a keyword type' => ['sizeof(unsigned int)', '(sizeof (unsigned int@30)@21)@14'];
        yield 'alignof of a structure' => ['alignof(struct S)', '(alignof struct S@22)@14'];
        yield 'sizeof of a type with an alignment specifier' => [
            'sizeof(alignas(8) int)',
            '(sizeof {alignas(8@29)@21 int@32}@21)@14',
        ];
        yield 'cast to a type with a storage class' => ['(static int)1', '(cast {static@15 int@22}@15 1@26)@14'];
        yield 'sizeof of an alignment specifier alone' => ['sizeof(alignas(8))', '(sizeof {alignas(8@29)@21 _}@21)@14'];

        yield 'chain of postfix operators' => ['p->m.n[2]', '([] (. (-> p@14 m@17)@14 n@19)@14 2@21)@14'];
        yield 'digraph subscript' => ['a<:1:>', '([] a@14 1@17)@14'];
        yield 'comma in a subscript' => ['a[1, 2]', '([] a@14 (, 1@16 2@19)@16)@14'];
        yield 'subscript of a compound literal' => ['(T){1}[0]', '([] (compound T@15 {1@18}@17)@14 0@21)@14'];
        yield 'function call' => ['f(1, x)', '(call f@14 1@16 x@19)@14'];
        yield 'function call without arguments' => ['f()', '(call f@14)@14'];
        yield 'call of a call' => ['f(1)(2)[0]', '([] (call (call f@14 1@16)@14 2@19)@14 0@22)@14'];
        yield 'call in parentheses after a lone identifier is a cast' => ['(T)(1)', '(cast T@15 (paren 1@18)@17)@14'];
        yield 'call of a parenthesized name' => ['(f)()', '(call (paren f@15)@14)@14'];
        yield 'call in a sum' => ['1 + f(2) * 3', '(+ 1@14 (* (call f@18 2@20)@18 3@25)@18)@14'];

        yield 'every level of precedence' => [
            'a || b && c | d ^ e & f == g < h << i + j * k',
            '(|| a@14 (&& b@19 (| c@24 (^ d@28 (& e@32 (== f@36 (< g@41 (<< h@45 (+ i@50 (* j@54 k@58)@54)@50)@45)'
                . '@41)@36)@32)@28)@24)@19)@14',
        ];
        yield 'left associativity after a tighter operator' => [
            'a * b - c * d - e',
            '(- (- (* a@14 b@18)@14 (* c@22 d@26)@22)@14 e@30)@14',
        ];
        yield 'operators of one level' => ['a < b == c > d', '(== (< a@14 b@18)@14 (> c@23 d@27)@23)@14'];
        yield 'parenthesized operand inside the expression' => [
            'a + (b) * c',
            '(+ a@14 (* (paren b@19)@18 c@24)@18)@14',
        ];

        yield 'sizeof of an expression' => ['sizeof x', '(sizeof x@21)@14'];
        yield 'sizeof of a type' => ['sizeof(T)', '(sizeof T@21)@14'];
        yield 'sizeof of a subscript in parentheses' => ['sizeof(x)[0]', '(sizeof ([] (paren x@21)@20 0@24)@20)@14'];
        yield 'sizeof of a member in parentheses' => ['sizeof(x).a', '(sizeof (. (paren x@21)@20 a@24)@20)@14'];
        yield 'sizeof of a pointer member in parentheses' => [
            'sizeof(x)->a',
            '(sizeof (-> (paren x@21)@20 a@25)@20)@14',
        ];
        yield 'sizeof of a nested product in parentheses' => [
            'sizeof(a * (b * (c)))',
            '(sizeof (paren (* a@21 (paren (* b@26 (paren c@31)@30)@26)@25)@21)@20)@14',
        ];
        yield 'sizeof of a parenthesized expression' => ['sizeof(1 + 2)', '(sizeof (paren (+ 1@21 2@25)@21)@20)@14'];
        yield 'sizeof of sizeof' => ['sizeof sizeof x', '(sizeof (sizeof x@28)@21)@14'];
        yield 'sizeof in a sum' => ['sizeof(T) + 1', '(+ (sizeof T@21)@14 1@26)@14'];
        yield 'alignof' => ['alignof(T)', '(alignof T@22)@14'];
        yield '_Alignof' => ['_Alignof(T)', '(alignof T@23)@14'];
        yield '__alignof__' => ['__alignof__(T)', '(alignof T@26)@14'];
        yield 'alignof of an expression' => ['__alignof__ x', '(alignof x@26)@14'];
        yield 'alignof of a subscript in parentheses' => [
            '__alignof__(x)[0]',
            '(alignof ([] (paren x@26)@25 0@29)@25)@14',
        ];
        yield 'alignof of an expression that reads as a type' => [
            '__alignof__(x[0])',
            '(alignof (paren ([] x@26 0@28)@26)@25)@14',
        ];
        yield 'alignof of a compound literal' => ['alignof(int){1}', '(alignof (compound int@22 {1@27}@26)@21)@14'];
        yield 'sizeof of a call without arguments' => ['sizeof(f())', '(sizeof (paren (call f@21)@21)@20)@14'];
        yield 'sizeof of a call' => ['sizeof(f(x, y))', '(sizeof (paren (call f@21 x@23 y@26)@21)@20)@14'];
        yield 'sizeof of a call of a name in parentheses' => [
            'sizeof(f)(1)',
            '(sizeof (call (paren f@21)@20 1@24)@20)@14',
        ];
        yield 'sizeof of a function type' => ['sizeof(T(void))', '(sizeof (fn T@21 (void@23@23))@22)@14'];
        yield 'sizeof of a function type with a pointer parameter' => [
            'sizeof(T(U *))',
            '(sizeof (fn T@21 ((ptr U@23)@25@23))@22)@14',
        ];

        yield 'generic selection' => [
            '_Generic(x, int: 1, default: 0)',
            '(_Generic x@23 int@26:1@31@26 default:0@43@34)@14',
        ];
        yield 'generic selection of a type name' => [
            '_Generic(int, T *: 1)',
            '(_Generic int@23 (ptr T@28)@30:1@33@28)@14',
        ];
        yield 'generic selection of a lone name' => ['_Generic(a, T: 1)', '(_Generic a@23 T@26:1@29@26)@14'];
        yield 'cast of a generic selection' => [
            '(T)_Generic(x, default: 0)',
            '(cast T@15 (_Generic x@26 default:0@38@29)@17)@14',
        ];

        yield 'statement expression' => ['({ int y = 1; y; })', '({ int y = 1; y; })@14'];
        yield 'cast of a statement expression' => ['(T)({ 1; })', '(cast T@15 ({ 1; })@17)@14'];
        yield 'statement expression in a sum' => ['1 + ({ 2; })', '(+ 1@14 ({ 2; })@18)@14'];

        yield 'offsetof' => [
            '__builtin_offsetof(struct S, a)',
            '(__builtin_offsetof struct S@33 a@43)@14',
        ];
        yield 'offsetof of a nested member and an element' => [
            '__builtin_offsetof(T, a.b[2].c)',
            '(__builtin_offsetof T@33 (. ([] (. a@36 b@38)@36 2@40)@36 c@43)@36)@14',
        ];
        yield 'built-in function of types' => [
            '__builtin_types_compatible_p(int, T *)',
            '(__builtin_types_compatible_p int@43 (ptr T@48)@50)@14',
        ];
        yield 'name of a built-in function without a parenthesis' => ['__builtin_va_list', '__builtin_va_list@14'];
        yield 'built-in function without arguments' => ['__builtin_huge_val()', '(__builtin_huge_val)@14'];
        yield 'built-in function with a space before the parenthesis' => [
            '__builtin_constant_p (1)',
            '(__builtin_constant_p 1@36)@14',
        ];
        yield 'built-in function in a sum' => [
            '1 + __builtin_offsetof(T, a)',
            '(+ 1@14 (__builtin_offsetof T@37 a@40)@18)@14',
        ];
        yield 'cast of a built-in function' => [
            '(T)__builtin_offsetof(U, a)',
            '(cast T@15 (__builtin_offsetof U@36 a@39)@17)@14',
        ];

        foreach (NodePrinter::BINARY_OPERATORS as $operator) {
            yield 'binary ' . $operator => $operator === ','
                ? ['(a , b)', '(paren (, a@15 b@19)@15)@14']
                : ['a ' . $operator . ' b', \sprintf('(%s a@14 b@%d)@14', $operator, 17 + \strlen($operator))];
        }

        foreach (NodePrinter::UNARY_OPERATORS as $operator) {
            yield 'unary ' . $operator => [$operator . 'a', \sprintf('(%su a@15)@14', $operator)];
        }
    }

    #[DataProvider('expressionsDataProvider')]
    public function testExpressionIsParsed(string $expression, string $expected): void
    {
        self::assertSame($expected, NodePrinter::printWithOffsets(self::parseCondition($expression)));
    }

    public function testLoneNameInSizeofIsATypeName(): void
    {
        $node = self::parseCondition('sizeof(T)');

        self::assertInstanceOf(SizeOfTypeExpression::class, $node);
        self::assertInstanceOf(NamedType::class, $node->type);
    }

    public function testNameInAnExpressionRefersToTheIdentifier(): void
    {
        $node = self::parseCondition('(a)');

        self::assertInstanceOf(ParenExpression::class, $node);
        self::assertInstanceOf(IdentifierExpression::class, $node->expression);
        self::assertSame('a', $node->expression->name->value);
        self::assertSame(15, $node->expression->name->offset);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, class-string<Literal>}>
     */
    public static function literalsDataProvider(): iterable
    {
        yield 'decimal integer constant' => ['42', DecIntLiteral::class];
        yield 'zero' => ['0', OctIntLiteral::class];
        yield 'zeros' => ['00', OctIntLiteral::class];
        yield 'octal integer constant' => ['017', OctIntLiteral::class];
        yield 'hexadecimal integer constant' => ['0x1', HexIntLiteral::class];
        yield 'binary integer constant' => ['0b1', BinIntLiteral::class];
        yield 'decimal floating constant' => ['1.5', DecFloatLiteral::class];
        yield 'decimal floating constant without the fraction' => ['1.', DecFloatLiteral::class];
        yield 'decimal floating constant without the whole part' => ['.5', DecFloatLiteral::class];
        yield 'decimal floating constant with an octal looking whole part' => ['08.5', DecFloatLiteral::class];
        yield 'decimal floating constant of zero' => ['0e1', DecFloatLiteral::class];
        yield 'hexadecimal floating constant' => ['0x1p3', HexFloatLiteral::class];
        yield 'character constant' => ["'a'", CharLiteral::class];
        yield 'string literal' => ['"a"', StringLiteral::class];
        yield 'true' => ['true', BoolLiteral::class];
        yield 'false' => ['false', BoolLiteral::class];
        yield 'nullptr' => ['nullptr', NullptrLiteral::class];
    }

    /**
     * @param class-string<Literal> $class
     */
    #[DataProvider('literalsDataProvider')]
    public function testLiteralBuildsItsNode(string $expression, string $class): void
    {
        self::assertInstanceOf($class, self::parseCondition($expression));
    }

    public function testBoolLiteralsKeepTheirValues(): void
    {
        $true = self::parseCondition('true');
        $false = self::parseCondition('false');

        self::assertInstanceOf(BoolLiteral::class, $true);
        self::assertInstanceOf(BoolLiteral::class, $false);
        self::assertTrue($true->value);
        self::assertFalse($false->value);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, string}>
     */
    public static function invalidExpressionsDataProvider(): iterable
    {
        yield 'missing right operand' => ['1 +', 17, ')'];
        yield 'unclosed parenthesis' => ['(1', 17, ';'];
        yield 'conditional without else' => ['a ? b', 19, ')'];
        yield 'assignment' => ['a = 1', 16, '='];
        yield 'leading assignment' => ['= 1', 14, '='];
        yield 'function call with an empty argument' => ['f(1, )', 19, ')'];
        yield 'generic selection without associations' => ['_Generic(x)', 24, ')'];
        yield 'generic association without an expression' => ['_Generic(x, int:)', 30, ')'];
        yield 'statement expression without parentheses' => ['{ 1; }', 14, '{'];
        yield 'increment' => ['a++', 15, '++'];
        yield 'sizeof without an operand' => ['sizeof', 20, ')'];
        yield 'cast without an operand' => ['(T)~', 18, ')'];
        yield 'unclosed subscript' => ['a[1', 17, ')'];
        yield 'member without a name' => ['a.', 16, ')'];
        yield 'member named by a number' => ['a->1', 17, '1'];
        yield 'empty parentheses' => ['()', 15, ')'];
        yield 'operand after sizeof of a type' => ['sizeof(T)x', 23, 'x'];
        yield 'comma outside parentheses' => ['a, b', 17, 'b'];
        yield 'sign after a hexadecimal constant ending with E' => ['0xE+1', 14, '0xE+1'];
        yield 'alignof without an operand' => ['alignof', 21, ')'];
        yield 'built-in function with an empty argument' => ['__builtin_offsetof(, a)', 33, ','];
        yield 'built-in function of a member written with a dot' => ['__builtin_offsetof(T, .a)', 36, '.'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidExpressionsDataProvider')]
    public function testInvalidExpressionIsRejected(string $expression, int $offset, string $fragment): void
    {
        self::assertSyntaxError('static_assert(' . $expression . ');', $offset, $fragment);
    }

    /**
     * @param non-empty-string $expression
     */
    private static function parseCondition(string $expression): Expression
    {
        return self::parseSingle('static_assert(' . $expression . ');', StaticAssertDeclaration::class)->condition;
    }
}
