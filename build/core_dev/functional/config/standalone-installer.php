<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once realpath(__DIR__ . '/../../../../app/autoload.php');
define('MAGENTO_CLI_PATH', realpath(BP . '/bin/magento'));

/* Uninstall Magento */
$uninstallCmd = 'php -f ' . MAGENTO_CLI_PATH . ' setup:uninstall -n';
passthru($uninstallCmd, $exitCode);
if ($exitCode) {
    exit($exitCode);
}

/* Get module list file to run test */
$opt = getopt('', ['module-list-file::', 'db-split::', 'db-name-quote::', 'db-name-sales::']);
$enableModules = [];
if (!empty($opt['module-list-file'])) {
    $moduleListFile = $opt['module-list-file'];
    // if value is undefined, bamboo will insert the variable definition literally as "${env.bamboo_...}"
    if (!preg_match('/^\$\{/', $moduleListFile)) {
        if (!is_file($moduleListFile)) {
            throw new Exception("The specified module list file does not exist: " . $moduleListFile);
        }
        $enableModules = file($moduleListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
}
$splitDb = [];
if ($opt['db-split'] == true && !empty($opt['db-name-quote']) && !empty($opt['db-name-sales'])) {
    $splitDb['quote'] = $opt['db-name-quote'];
    $splitDb['sales'] = $opt['db-name-sales'];
}

/* Prepare installation options */
$installConfig = require_once 'install.php';
$installOptions = isset($installConfig['install_options']) ? $installConfig['install_options'] : [];
if ($enableModules) {
    $installOptions['enable_modules'] = implode(',', $enableModules);
}
$installOptionsNoValue = isset($installConfig['install_options_no_value'])
    ? $installConfig['install_options_no_value']
    : [];

/* Install application */
if ($installOptions) {
    // Create main DB
    $createDb = "mysql -u{$installOptions['db-user']} -p{$installOptions['db-password']} -h{$installOptions['db-host']}"
        . " -e 'DROP DATABASE IF EXISTS {$installOptions['db-name']}; CREATE DATABASE {$installOptions['db-name']};'";
    passthru($createDb, $exitCode);
    if ($exitCode) {
        exit($exitCode);
    }
    // Prepare install arguments
    $installCmd = 'php -f ' . MAGENTO_CLI_PATH . ' setup:install';
    foreach ($installOptions as $optionName => $optionValue) {
        $installCmd .= sprintf(' --%s=%s', $optionName, escapeshellarg($optionValue));
    }
    foreach ($installOptionsNoValue as $optionName) {
        $installCmd .= sprintf(' --%s', $optionName);
    }
    // Run install
    passthru($installCmd, $exitCode);
    if ($exitCode) {
        exit($exitCode);
    }

    /* Run CLI command for DB decomposition on 3 masters */
    if (!empty($splitDb)) {
        foreach ($splitDb as $splitDbCode => $dbName) {
            // Create required DB
            $createDb = "mysql -u{$installOptions['db-user']} -p{$installOptions['db-password']} "
                . "-h{$installOptions['db-host']} -e 'DROP DATABASE IF EXISTS $dbName; CREATE DATABASE $dbName;'";
            passthru($createDb, $exitCode);
            if ($exitCode) {
                exit($exitCode);
            }
            // Decompose DB
            $splitQuote = 'php -f ' . MAGENTO_CLI_PATH . ' setup:db-schema:split-' . $splitDbCode
                . ' --host="' . $installOptions['db-host'] . '"'
                . ' --dbname="' . $dbName . '"'
                . ' --username="' . $installOptions['db-user'] . '"'
                . ' --password="' . $installOptions['db-password'] . '"';
            passthru($splitQuote, $exitCode);
            if ($exitCode) {
                exit($exitCode);
            }
        }
    }

    /* Dump main database */
    $dumpCommand = "mysqldump -u{$installOptions['db-user']} -p{$installOptions['db-password']} "
        . "{$installOptions['db-name']} -h{$installOptions['db-host']} > {$installOptions['db-name']}.sql";
    passthru($dumpCommand, $exitCode);
    if ($exitCode) {
        exit($exitCode);
    }
    /* Dump additional databases */
    if (!empty($splitDb)) {
        foreach ($splitDb as $dbName) {
            $dumpCommand = "mysqldump -u{$installOptions['db-user']} -p{$installOptions['db-password']} "
                . "{$dbName} -h{$installOptions['db-host']} > {$dbName}.sql";
            passthru($dumpCommand, $exitCode);
            if ($exitCode) {
                exit($exitCode);
            }
        }
    }
}

/* Unset declared global variables to release PHPUnit from maintaining their values between tests */
unset($installCmd, $installConfigFile, $installConfig, $installExitCode);
