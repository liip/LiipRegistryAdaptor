<?php

namespace Liip\Registry\Adaptor\Decorator;

use Liip\Registry\Adaptor\Tests\RegistryTestCase;

class NoOpDecoratorTest extends RegistryTestCase
{

    /**
     * @covers \Liip\Registry\Adaptor\Decorator\NoOpDecorator::normalizeValue
     */
    public function testNormalizeValue()
    {
        $decorator = new NoOpDecorator();

        $value = 'tux';

        $this->assertSame($value, $decorator->normalizeValue($value));
    }

    /**
     * @covers \Liip\Registry\Adaptor\Decorator\NoOpDecorator::denormalizeValue
     */
    public function testDenormalizeArray()
    {
        $decorator = new NoOpDecorator();

        $value = array('tux');

        $this->assertSame($value, $decorator->denormalizeValue($value));
    }

}
