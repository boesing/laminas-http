<?php

/**
 * @see       https://github.com/laminas/laminas-http for the canonical source repository
 * @copyright https://github.com/laminas/laminas-http/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-http/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Http\Header;

use Laminas\Http\Header\Exception\InvalidArgumentException;
use Laminas\Http\Header\GenericHeader;
use PHPUnit_Framework_TestCase as TestCase;

class GenericHeaderTest extends TestCase
{
    /**
     * @param string $name
     * @dataProvider validFieldNameChars
     */
    public function testValidFieldName($name)
    {
        try {
            new GenericHeader($name);
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                $e->getMessage(),
                'Header name must be a valid RFC 7230 (section 3.2) field-name.'
            );
            $this->fail('Allowed char rejected: ' . ord($name)); // For easy debug
        }
    }

    /**
     * @param string $name
     * @dataProvider invalidFieldNameChars
     */
    public function testInvalidFieldName($name)
    {
        try {
            new GenericHeader($name);
            $this->fail('Invalid char allowed: ' . ord($name)); // For easy debug
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                $e->getMessage(),
                'Header name must be a valid RFC 7230 (section 3.2) field-name.'
            );
        }
    }

    /**
     * @group 7295
     */
    public function testDoesNotReplaceUnderscoresWithDashes()
    {
        $header = new GenericHeader('X_Foo_Bar');
        $this->assertEquals('X_Foo_Bar', $header->getFieldName());
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testPreventsCRLFAttackViaFromString()
    {
        $this->setExpectedException('Laminas\Http\Header\Exception\InvalidArgumentException');
        $header = GenericHeader::fromString("X_Foo_Bar: Bar\r\n\r\nevilContent");
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testPreventsCRLFAttackViaConstructor()
    {
        $this->setExpectedException('Laminas\Http\Header\Exception\InvalidArgumentException');
        $header = new GenericHeader('X_Foo_Bar', "Bar\r\n\r\nevilContent");
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testProtectsFromCRLFAttackViaSetFieldName()
    {
        $header = new GenericHeader();
        $this->setExpectedException('Laminas\Http\Header\Exception\InvalidArgumentException', 'valid');
        $header->setFieldName("\rX-\r\nFoo-\nBar");
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testProtectsFromCRLFAttackViaSetFieldValue()
    {
        $header = new GenericHeader();
        $this->setExpectedException('Laminas\Http\Header\Exception\InvalidArgumentException');
        $header->setFieldValue("\rSome\r\nCLRF\nAttack");
    }

    /**
     * Valid field name characters.
     *
     * @return string[]
     */
    public function validFieldNameChars()
    {
        return [
            ['!'],
            ['#'],
            ['$'],
            ['%'],
            ['&'],
            ["'"],
            ['*'],
            ['+'],
            ['-'],
            ['.'],
            ['0'], // Begin numeric range
            ['9'], // End numeric range
            ['A'], // Begin upper range
            ['Z'], // End upper range
            ['^'],
            ['_'],
            ['`'],
            ['a'], // Begin lower range
            ['z'], // End lower range
            ['|'],
            ['~'],
        ];
    }

    /**
     * Invalid field name characters.
     *
     * @return string[]
     */
    public function invalidFieldNameChars()
    {
        return [
            ["\x00"], // Min CTL invalid character range.
            ["\x1F"], // Max CTL invalid character range.
            ['('],
            [')'],
            ['<'],
            ['>'],
            ['@'],
            [','],
            [';'],
            [':'],
            ['\\'],
            ['"'],
            ['/'],
            ['['],
            [']'],
            ['?'],
            ['='],
            ['{'],
            ['}'],
            [' '],
            ["\t"],
            ["\x7F"], // DEL CTL invalid character.
        ];
    }
}
