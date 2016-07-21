#!/usr/bin/php
<?php
/**
 * Script for preparing migration tool for Magento edition
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// get CLI options, define variables
define(
'SYNOPSIS',
<<<SYNOPSIS
php -f prepare_edition.php --
    [--edition="ce(default)|ee"]
    --source-dir="<directory>"
SYNOPSIS
);
$options = getopt('', [
    'edition::',
    'source-dir:',
    'keep-git'
]);
$requiredArgs = ['source-dir'];
foreach ($requiredArgs as $arg) {
    if (empty($options[$arg])) {
        echo SYNOPSIS;
        exit(1);
    }
}

require_once __DIR__ . '/../publication/functions.php';

$edition = (isset($options['edition']) && 'ee' == $options['edition']) ? 'ee' : 'ce';
$sourceDir = $options['source-dir'];

try {
    if (!file_exists($sourceDir . '/composer.json')) {
        throw new \InvalidArgumentException("Invalid package source dir specified: {$sourceDir}");
    }

    $file = __DIR__ . '/edition-' . $edition . '.txt';
    if (file_exists($file) && file_get_contents($file)) {
        $extruderCommand = 'php -f ' . __DIR__ . '/../extruder.php -- -v -w ' . escapeshellarg($sourceDir);
        $extruderCommand .= ' -l ' . escapeshellarg($file);
        execVerbose($extruderCommand);
    }

    if (!isset($options['keep-git'])) {
        echo "Deleting Git";
        execVerbose('rm -r ' . $sourceDir . '/.git*');
    }

    echo "Prepare Migration Tool edition - completed" . PHP_EOL;
} catch (\Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
