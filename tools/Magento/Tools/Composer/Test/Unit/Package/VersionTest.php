<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Test\Unit\Package;

use Magento\Tools\Composer\Package\Version;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $version
     * @dataProvider validateDataProvider
     */
    public function testValidate($version)
    {
        Version::validate($version);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            ['1.0.0'],
            ['1.0.0-dev'],
            ['1.0.0-alpha'],
            ['1.0.0-alpha1'],
            ['1.0.0-beta2'],
            ['1.0.0-rc3'],
        ];
    }

    /**
     * @param string $version
     * @dataProvider validateExceptionDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testValidateException($version)
    {
        Version::validate($version);
    }

    /**
     * @return array
     */
    public function validateExceptionDataProvider()
    {
        return [
            ['1.0'],
            ['1.0.0-beta+12'],
        ];
    }
}
