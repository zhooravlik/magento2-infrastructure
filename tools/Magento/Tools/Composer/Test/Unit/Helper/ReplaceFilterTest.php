<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Test\Unit\Helper;

class ReplaceFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $name
     * @param bool $expected
     * @dataProvider isMagentoComponentDataProvider
     */
    public function testIsMagentoComponent($name, $expected)
    {
        $this->assertEquals($expected, \Magento\Tools\Composer\Helper\ReplaceFilter::isMagentoComponent($name));
    }

    /**
     * @return array
     */
    public function isMagentoComponentDataProvider()
    {
        return [
            ['magento/module', true],
            ['magento/module-foo', true],
            ['magento/theme', true],
            ['magento/theme-frontend', true],
            ['magento/theme-frontend-foo', true],
            ['magento/theme-adminhtml', true],
            ['magento/theme-adminhtml-bar', true],
            ['magento/language', true],
            ['magento/language-foo', true],
            ['magento/framework', true],
            ['magento/framework-bar', true],
            ['magento/anything-else', false],
            ['vendor/module', false],
            ['vendor/module-foo', false],
        ];
    }

    public function testMoveMagentoComponentsToRequire()
    {
        $replaceMap = [
            'magento/framework' => 'self.version',
            'magento/module-other-module' => 'self.version',
            'magento/module-sample-module' => 'self.version',
        ];
        $package = $this->getMockBuilder('Magento\Tools\Composer\Package\Package')
            ->disableOriginalConstructor()->getMock();
        $package->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['replace', null, $replaceMap],
                ['suggest', null, ['magento/module-fedex' => 'comment']],
                ['replace->magento/framework', null, 'self.version'],
                ['replace->magento/module-other-module', null, 'self.version'],
                ['replace->magento/module-sample-module', null, 'self.version'],
            ]);
        $package->expects($this->exactly(2))
            ->method('unsetProperty')
            ->withConsecutive(
                [$this->equalTo('replace->magento/module-other-module')],
                [$this->equalTo('replace->magento/module-sample-module')]
            );
        $package->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [$this->equalTo('require->magento/module-other-module'), $this->anything()],
                [$this->equalTo('require->magento/module-sample-module'), $this->anything()]
            );

        $replaceFilter = new \Magento\Tools\Composer\Helper\ReplaceFilter(__DIR__ . '/../_files');

        $replaceFilter->moveMagentoComponentsToRequire($package, false);
    }
}
