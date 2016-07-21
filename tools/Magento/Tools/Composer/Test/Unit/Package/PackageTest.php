<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Test\Unit\Package;

use Magento\Tools\Composer\Package\Package;

class PackageTest extends \PHPUnit_Framework_TestCase
{
    const SAMPLE_DATA = '{"foo":"1","bar":"2","baz":["3","4"],"nested":{"one":"5","two":"6"}}';

    /**
     * @var \StdClass
     */
    private $sampleJson;

    /**
     * @var Package
     */
    private $object;

    protected function setUp()
    {
        $this->sampleJson = json_decode(self::SAMPLE_DATA);
        $this->object = new Package($this->sampleJson, 'file');
    }

    public function testGetJson()
    {
        $this->assertInstanceOf('\StdClass', $this->object->getJson(false));
        $this->assertEquals($this->sampleJson, $this->object->getJson(false));
        $this->assertSame($this->sampleJson, $this->object->getJson(false));
        $this->assertEquals(self::SAMPLE_DATA . "\n", $this->object->getJson(true, 0));
        $this->assertEquals(
            json_encode($this->sampleJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            $this->object->getJson(true, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function testGetFile()
    {
        $this->assertEquals('file', $this->object->getFile());
    }

    public function testGet()
    {
        $this->assertSame('1', $this->object->get('foo'));
        $this->assertSame(['3', '4'], $this->object->get('baz'));
        $nested = $this->object->get('nested');
        $this->assertInstanceOf('\StdClass', $nested);
        $this->assertObjectHasAttribute('one', $nested);
        $this->assertEquals('5', $nested->one);
        $this->assertEquals('5', $this->object->get('nested->one'));
        $this->assertObjectHasAttribute('two', $nested);
        $this->assertEquals('6', $nested->two);
        $this->assertEquals('6', $this->object->get('nested->two'));
    }

    /**
     * @depends testGet
     */
    public function testSet()
    {
        $this->object->set('foo', '1.0');
        $this->assertSame('1.0', $this->object->get('foo'));
        $this->object->set('baz', ['3.0', '4.0']);
        $this->assertSame(['3.0', '4.0'], $this->object->get('baz'));
        $this->object->set('nested->one', '5.0');
        $this->assertSame('5.0', $this->object->get('nested->one'));
        $replace = new \StdClass();
        $this->object->set('nested', $replace);
        $this->assertSame($replace, $this->object->get('nested'));
    }

    public function testUnsetProperty()
    {
        $this->object->set('foo', '1.0');
        $this->assertSame('1.0', $this->object->get('foo'));
        $this->object->unsetProperty('foo');
        $this->assertFalse($this->object->get('foo'));
        $this->object->set('baz', ['3.0', '4.0']);
        $this->assertSame(['3.0', '4.0'], $this->object->get('baz'));
        $this->object->unsetProperty('baz');
        $this->assertFalse($this->object->get('baz'));
        $this->object->set('nested->one', '5.0');
        $this->assertSame('5.0', $this->object->get('nested->one'));
        $this->object->unsetProperty('nested->one');
        $this->assertFalse($this->object->get('nested->one'));
        $this->assertInstanceOf('\StdClass', $this->object->get('nested'));
        $replace = new \StdClass();
        $this->object->set('nested', $replace);
        $this->assertSame($replace, $this->object->get('nested'));
        $this->object->unsetProperty('nested');
        $this->assertFalse($this->object->get('nested'));
    }
}
