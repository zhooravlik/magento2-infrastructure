<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\Composer\Package;

/**
 * Helper class for working with composer-compatible versions
 */
class Version
{
    /**
     * Assert that a version has appropriate format
     *
     * @param string $version
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function validate($version)
    {
        $preRelease = '(alpha|beta|rc|dev|patch)\d?';
        if (!preg_match('/^\d+\.\d+\.\d+(\-(' . $preRelease . '))?$/', $version)) {
            throw new \InvalidArgumentException(
                "Wrong format of version number '$version'. Acceptable format is 'x.y.z[-<alpha|beta|rc|dev|patch>n]', "
                . "where x, y, z, n are positive numbers"
            );
        }
    }
}
