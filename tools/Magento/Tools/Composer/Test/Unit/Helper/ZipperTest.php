<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Tools\Composer\Test\Unit\Helper;

/**
 * Class ZipTest
 * @package Magento\Test\Tools\Composer\Helper
 */
class ZipperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Intial Setup
     * @return void
     */
    protected function setUp()
    {
        $destination = TESTS_TEMP_DIR;
        if (file_exists($destination . '/' . 'library.zip')) {
            unlink($destination . '/' . 'library.zip');
        }
    }

    /**
     * Test Zip
     * @return void
     */
    public function testZip()
    {
        $source = str_replace('\\', '/', realpath(__DIR__ . '/..' . '/_files/app'));
        $destination = TESTS_TEMP_DIR;

        $noOfZips = \Magento\Tools\Composer\Helper\Zipper::zip($source, $destination . '/' . 'library.zip', []);
        $this->assertFileExists($destination . '/' . 'library.zip');
        $this->assertEquals(sizeof($noOfZips), 1);
    }

    /**
     * Test ZipExclude
     * @return void
     */
    public function testZipExclude()
    {
        $source = str_replace('\\', '/', realpath(__DIR__ . '/..' . '/_files/app'));
        $destination = TESTS_TEMP_DIR;

        $exclude = [
            str_replace('\\', '/', realpath(__DIR__ . '/..')) . '/_files/app/code/Magento/OtherModule',
        ];

        \Magento\Tools\Composer\Helper\Zipper::zip($source, $destination . "/" . "library.zip", $exclude);
        $this->assertFileExists($destination . '/' . 'library.zip');

        $za = new \ZipArchive();

        $za->open($destination . '/' . 'library.zip');

        for ($i = 0; $i < $za->numFiles; $i++) {
            $stat = $za->statIndex($i);
            $this->assertNotContains($source . '/' . rtrim($stat['name'], '/'), $exclude);
        }
    }
}
