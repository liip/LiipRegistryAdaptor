<?php

namespace Liip\Registry\Adaptor\Decorator;

use Liip\Registry\Adaptor\Tests\RegistryTestCase;

class NormalizeDecoratorTest extends RegistryTestCase
{

    /**
     * @dataProvider normalizeValueDataprovider
     * @covers \Liip\Registry\Adaptor\Decorator\NormalizeDecorator::normalizeValue
     */
    public function testNormalizeValue($expected, $value)
    {
        $decorator = new NormalizeDecorator();

        $valueArray = $decorator->normalizeValue($value);

        $this->assertInternalType('array', $valueArray);
        $this->assertEquals($expected, $valueArray);
    }
    public static function normalizeValueDataprovider()
    {
        return array(
            'empty value' => array(array(), array()),
            'number value' => array(array('integer' => '1'), 1),
            'float value'  => array(array('double' => '1.1'), 1.1),
            'string value' => array(array('string' => '"blob"'), 'blob'),
            'array value' => array(array('array' => '{"tux":"gnu"}'), array('tux' => 'gnu')),
            'object value' => array(array('object' => '{"tux":"gnu"}'), (object) array('tux' => 'gnu')),
            'class instance value' => array(array('object' => '{"tux":"gnu"}'), new \ArrayObject(array('tux' => 'gnu'))),
        );
    }

    /**
     * @dataProvider denormalizeArrayDataprovider
     * @covers \Liip\Registry\Adaptor\Decorator\NormalizeDecorator::denormalizeValue
     */
    public function testDenormalizeArray($expected, $array)
    {
        $decorator = new NormalizeDecorator();

        $value = $decorator->denormalizeValue($array);

        $this->assertEquals($expected, $value);
    }
    public static function denormalizeArrayDataprovider()
    {
        return array(
            'normalized object array' => array(array((object) array('tux' => 'gnu')), array(array('object' => '{"tux":"gnu"}'))),
            'normalized number array' => array(array(1), array(array('integer' => 1))),
            'normalized float array'  => array(array(1.1), array(array('double'  => 1.1))),
            'normalized string array' => array(array('blob'), array(array('string' => '"blob"'))),
            'usual data' => array(array(array('tux' => 'mascott')), array(array('array' => '{"tux":"mascott"}'))),
            'no normalize data' => array(array((object)array('tux' => 'mascott')), array(array('{"tux":"mascott"}'))),
        );
    }

    /**
     * @covers \Liip\Registry\Adaptor\Decorator\NormalizeDecorator::denormalizeValue
     */
    public function testDenormalizeValueExpectingexception()
    {
        $decorator = new NormalizeDecorator();

        $this->setExpectedException('\Assert\InvalidArgumentException');
        $value = $decorator->denormalizeValue(array(array('{"tux":mascott}')));
    }

}
