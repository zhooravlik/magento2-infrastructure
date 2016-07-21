<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Magento\Test\Integrity;

use Magento\Framework\App\Utility\Files;

class ComposerRequireTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private static $root;

    /**
     * @var \stdClass
     */
    private static $rootJson;

    /**
     * @var array
     */
    private static $whitelist;

    public static function setUpBeforeClass()
    {
        self::$root = Files::init()->getPathToSource();
        self::$rootJson = json_decode(file_get_contents(self::$root . '/composer.json'), true);
        self::$whitelist = include '_files/composer_require/whitelist.php';
    }

    public function testPackages()
    {
        if (self::$rootJson['type'] === 'project') {
            $this->markTestSkipped('Skip this test for composer based builds');
        }
        $requiredPackagesInRoot =  array_keys(self::$rootJson['require']);
        $requiredPackagesInComponents = array_unique(array_merge($this->getComponentDependencies(), self::$whitelist));
        $packagesDiff = array_diff($requiredPackagesInRoot, $requiredPackagesInComponents);
        $this->assertSame(
            [],
            $packagesDiff ,
            'Please add the following packages ' . PHP_EOL . implode(PHP_EOL, $packagesDiff) . PHP_EOL
            . 'to the composer.json of the module that requires it. If it is absolutely required in '
            . 'root composer.json, please add it to the \'composer_require/whitelist.php\''
        );
    }

    /**
     * Gets dependencies of each composer.json of all components
     *
     * @return array
     */
    private function getComponentDependencies()
    {
        $composerFiles = [];
        $composerFiles  = array_merge(
            Files::init()->getComposerFiles(\Magento\Framework\Component\ComponentRegistrar::MODULE),
            $composerFiles
        );
        $composerFiles  = array_merge(
            Files::init()->getComposerFiles(\Magento\Framework\Component\ComponentRegistrar::LIBRARY),
            $composerFiles
        );
        $composerFiles  = array_merge(
            Files::init()->getComposerFiles(\Magento\Framework\Component\ComponentRegistrar::THEME),
            $composerFiles
        );
        $composerFiles  = array_merge(
            Files::init()->getComposerFiles(\Magento\Framework\Component\ComponentRegistrar::LANGUAGE),
            $composerFiles
        );

        $composerRequire = [];
        foreach($composerFiles as $composerFile) {
            $composerContents = json_decode(file_get_contents($composerFile[0]), true);
            $composerRequire = array_merge($composerRequire, array_keys($composerContents['require']));
        }
        return array_unique($composerRequire);
    }
}
