<?php
/**
 * Script for preparing package repositories of Magento components and product
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    throw new \RuntimeException("Can't find autoload file. Please, run 'composer install'");
}
require_once $autoloadPath;
if (ini_get('date.timezone') == '') {
    date_default_timezone_set(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::DEFAULT_TIMEZONE);
}
