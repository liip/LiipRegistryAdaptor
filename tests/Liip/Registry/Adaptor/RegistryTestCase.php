<?php
namespace Liip\Registry\Adaptor\Tests;

use lapistano\ProxyObject\ProxyBuilder;

abstract class RegistryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Provides an instance of the ProxyBuilder
     *
     * @param string $className
     *
     * @return \lapistano\ProxyObject\ProxyBuilder
     */
    protected function getProxyBuilder($className)
    {
        return new ProxyBuilder($className);
    }

    /**
     * Provides a stub of the \Assert\Assertion class;
     *
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAssertionObjectMock(array $methods = array())
    {
        return $this->getMockBuilder('\\Assert\\Assertion')
            ->setMethods($methods)
            ->getMock();
    }
}
