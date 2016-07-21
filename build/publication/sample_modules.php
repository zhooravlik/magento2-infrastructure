#!/usr/bin/php
<?php
/**
 * Script for preparing Magento sample modules packages
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// get CLI options, define variables
define(
'SYNOPSIS',
<<<SYNOPSIS
php -f sample_modules.php --
    --repo="<repository>" [--repo-dir="<directory>"]
    --target-packages-dir="<directory>"
SYNOPSIS
);
$options = getopt('', [
    'repo:', 'repo-dir::',
    'target-packages-dir:'
]);
$requiredArgs = ['repo', 'target-packages-dir'];
foreach ($requiredArgs as $arg) {
    if (empty($options[$arg])) {
        echo SYNOPSIS;
        exit(1);
    }
}

require_once __DIR__ . '/functions.php';

$repo = $options['repo'];
$repoDir = (isset($options['repo-dir']) ? $options['repo-dir'] : __DIR__ . '/_sample-modules');
$packagesTargetDir = $options['target-packages-dir'];

try {
    // Clone sample modules
    execVerbose('git clone -- %s %s', $repo, $repoDir);

    // Create a zip archive for each module
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/generic-archiver.php -- --dir=%s --output=%s',
        $repoDir, $packagesTargetDir
    );
} catch (\Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
