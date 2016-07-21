<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once realpath(__DIR__ . '/../../../../app/autoload.php');

/* Prepare installation options */
$installConfig = require_once 'install.php';
$installOptions = isset($installConfig['install_options']) ? $installConfig['install_options'] : [];
$installOptionsNoValue = isset($installConfig['install_options_no_value'])
    ? $installConfig['install_options_no_value']
    : [];

// check sample data installation
if (in_array('use-sample-data', $installOptionsNoValue)) {
    $sampleDataErrorFile = realpath(BP . '/var/sample-data-error.flag');
    if ($sampleDataErrorFile) {
        if ('error' === file_get_contents($sampleDataErrorFile)) {
            echo 'Error while installing of Sample Data, please, see the logs for more details.';
            exit(1);
        }
    }
}
