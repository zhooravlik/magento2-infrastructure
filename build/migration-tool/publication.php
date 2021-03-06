#!/usr/bin/php
<?php
/**
 * Script for preparing Magento migration tool packages
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// get CLI options, define variables
define(
'SYNOPSIS',
<<<SYNOPSIS
php -f publication.php --
    [--edition="ce(default)|ee"]
    --repo="<repository>" [--repo-dir="<directory>"]
    --target-packages-dir="<directory>"
SYNOPSIS
);
$options = getopt('', [
    'edition::',
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

require_once __DIR__ . '/../publication/functions.php';

$edition = (isset($options['edition']) && 'ee' == $options['edition']) ? 'ee' : 'ce';
$repo = $options['repo'];
$repoDir = (isset($options['repo-dir']) ? $options['repo-dir'] : __DIR__ . '/_data-migration-tool');
$packagesTargetDir = $options['target-packages-dir'];

try {
    // clone migration tool
    execVerbose('git clone -- %s %s', $repo, $repoDir);

    // publish migration tool script package
    execVerbose(
        'php -f ' . __DIR__ . '/prepare_edition.php -- --edition=%s --source-dir=%s',
        $edition, $repoDir
    );
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/generic-archiver.php -- --dir=%s --output=%s',
        $repoDir, $packagesTargetDir
    );
} catch (\Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
