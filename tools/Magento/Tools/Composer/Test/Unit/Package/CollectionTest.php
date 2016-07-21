<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Test\Unit\Package;

use Magento\Tools\Composer\Package\Collection;
use Magento\Tools\Composer\Package\Package;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Collection
     */
    private $object;

    protected function setUp()
    {
        $this->object = new Collection();
    }

    public function testAddGetPackages()
    {
        $packageOne = new Package(json_decode('{"name":"one"}'), '...');
        $packageTwo = new Package(json_decode('{"name":"two"}'), '...');
        $this->object->add($packageOne);
        $this->object->add($packageTwo);
        $this->assertSame($packageOne, $this->object->getPackage('one'));
        $this->assertSame($packageTwo, $this->object->getPackage('two'));
        $this->assertSame(['one' => $packageOne, 'two' => $packageTwo], $this->object->getPackages());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No package name found in the file: /test/composer.json
     */
    public function testAddNoName()
    {
        $this->object->add(new Package(new \StdClass(), '/test/composer.json'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The package 'test' already exists in collection
     */
    public function testAddSameName()
    {
        $object = new \StdClass();
        $object->name = 'test';
        $this->object->add(new Package($object, '...'));
        $this->object->add(new Package($object, '...'));
    }

    /**
     * @param string|bool $updateDependent
     * @param string $expectedBarFoo
     * @param string $expectedBazFoo
     * @param string $expectedBazBar
     * @dataProvider setVersionDataProvider
     */
    public function testSetVersion($updateDependent, $expectedBarFoo, $expectedBazFoo, $expectedBazBar)
    {
        $object = new Collection($updateDependent);
        $result = [
            'foo' => json_decode('{"name":"foo","version":"1.0.0"}'),
            'bar' => json_decode('{"name":"bar","version":"1.0.0","require":{"foo":"1.0.0"}}'),
            'baz' => json_decode('{"name":"baz","version":"1.0.0","require":{"foo":"1.0.0","bar":"1.0.0"}}'),
            'qux' => json_decode('{"name":"qux","version":"1.0.0","replace":{"foo":"1.0.0"}}'),
        ];
        $object->add(new Package($result['foo'], '...'));
        $object->add(new Package($result['bar'], '...'));
        $object->add(new Package($result['baz'], '...'));
        $object->add(new Package($result['qux'], '...'));
        $object->setVersion('foo', '2.0.0', $updateDependent);
        $this->assertEquals('2.0.0', $result['foo']->version);
        $this->assertEquals($expectedBarFoo, $result['bar']->require->foo);
        $this->assertEquals($expectedBazFoo, $result['baz']->require->foo);
        $this->assertEquals($expectedBazBar, $result['baz']->require->bar);
        $this->assertEquals('1.0.0', $result['qux']->replace->foo);
    }

    /**
     * @return array
     */
    public function setVersionDataProvider()
    {
        return [
            [Collection::UPDATE_DEPENDENT_NONE, '1.0.0', '1.0.0', '1.0.0'],
            [Collection::UPDATE_DEPENDENT_EXACT, '2.0.0', '2.0.0', '1.0.0'],
            [Collection::UPDATE_DEPENDENT_WILDCARD, '2.0.*', '2.0.*', '1.0.0'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Wrong format of version number '10.0.0-alpha.87'
     */
    public function testSetVersionInvalidFormat()
    {
        $this->object->setVersion('...', '10.0.0-alpha.87');
    }
}
