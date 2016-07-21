#!/usr/bin/php
<?php
/**
 * Script for preparing Magento sample data packages
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// get CLI options, define variables
define(
'SYNOPSIS',
<<<SYNOPSIS
php -f sample_data.php --
    [--edition="ce(default)|ee"]
    --ce-repo="<repository>" [--ce-repo-dir="<directory>"] [--ce-branch="<branch>"]
    [--ee-repo="<repository>" [--ee-repo-dir="<directory>"] [--ee-branch="<branch>"]]
    --target-packages-dir="<directory>"
SYNOPSIS
);
$options = getopt('', [
    'edition::',
    'ce-repo:', 'ce-repo-dir::', 'ce-branch::',
    'ee-repo::', 'ee-repo-dir::', 'ee-branch::',
    'target-packages-dir:'
]);
$requiredArgs = ['ce-repo', 'target-packages-dir'];
foreach ($requiredArgs as $arg) {
    if (empty($options[$arg])) {
        echo SYNOPSIS;
        exit(1);
    }
}

require_once __DIR__ . '/functions.php';

$edition = (isset($options['edition']) && 'ee' == $options['edition']) ? 'ee' : 'ce';
$ceRepo = $options['ce-repo'];
$ceRepoDir = (isset($options['ce-repo-dir']) ? $options['ce-repo-dir'] : __DIR__ . '/_sample-data');
$packagesTargetDir = $options['target-packages-dir'];

if ('ee' == $edition) {
    $eeRepo = $options['ee-repo'];
    $eeRepoDir = (isset($options['ee-repo-dir']) ? $options['ee-repo-dir'] : __DIR__ . '/_sample-data-ee');
}
try {
    // clone sample data ce
    execVerbose('git clone -- %s %s', $ceRepo, $ceRepoDir);
    if (isset($options['ce-branch'])) {
        execVerbose('cd %s ; git checkout %s;', $ceRepoDir, $options['ce-branch']);
    }

    // prepare CE sample data packages
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/generic-archiver.php -- --dir=%s --output=%s',
        $ceRepoDir . '/app/code/Magento', $packagesTargetDir
    );

    if ('ee' == $edition) {
        // clone sample data ee
        execVerbose('git clone -- %s %s', $eeRepo, $eeRepoDir);
        if (isset($options['ee-branch'])) {
            execVerbose('cd %s ; git checkout %s;', $eeRepoDir, $options['ee-branch']);
        }

        // prepare EE sample data packages
        execVerbose(
            'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/generic-archiver.php -- --dir=%s --output=%s',
            $eeRepoDir . '/app/code/Magento', $packagesTargetDir
        );
    }

    // prepare Sample Data Media package
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/generic-archiver.php -- --dir=%s --output=%s',
        $ceRepoDir . '/pub/media', $packagesTargetDir
    );
} catch (\Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
