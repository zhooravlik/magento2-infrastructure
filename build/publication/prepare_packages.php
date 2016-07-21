#!/usr/bin/php
<?php
/**
 * Script for preparing package repositories of Magento components and project
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// get CLI options, define variables
define(
    'SYNOPSIS',
<<<SYNOPSIS
php -f prepare_packages.php --
    --source-dir="<directory>" is required
    --target-packages-repo="<repository>" is required for ee edition
    --project-repo="<repository>" is required for ce edition, optional for ee edition,
    [--edition=<ce|ee>] is optional, default is ce
    [--target-packages-dir="<directory>"] is optional, default is _tmp_packages
    [--target-project-dir="<directory>"] is optional, default is _tmp_project
SYNOPSIS
);
$options = getopt('', [
    'edition:',
    'source-dir:', 'target-packages-dir:', 'target-packages-repo:',
    'project-repo:', 'target-project-dir:'
]);
$edition = isset($options['edition']) ? $options['edition'] : 'ce';
$requiredArgs = $edition === 'ce' ? ['source-dir', 'project-repo'] : ['source-dir', 'target-packages-repo'];
foreach ($requiredArgs as $arg) {
    if (empty($options[$arg])) {
        echo SYNOPSIS;
        exit(1);
    }
}

require_once __DIR__ . '/functions.php';

$sourceDir = $options['source-dir'];
$packagesTargetDir = isset($options['target-packages-dir']) ?
    $options['target-packages-dir'] : __DIR__ . '/_tmp_packages';
$projectTargetDir = isset($options['target-project-dir']) ?
    $options['target-project-dir'] : __DIR__ . '/_tmp_project';
$packagesTargetRepo = isset($options['target-packages-repo']) ? $options['target-packages-repo'] : '';

try {
    /**
     * Prepare base to work with and to not spoil original repository
     */
    $sourceBaseDir = __DIR__ . '/_tmp_base_source_' . time();
    execVerbose("git clone %s %s", $sourceDir, $sourceBaseDir);

    // Create Project Package
    switch ($edition) {
        case 'ee':
            createEEProjectPackage(
                $sourceBaseDir,
                $packagesTargetDir,
                $packagesTargetRepo,
                $projectTargetDir
            );
            break;
        default:
            createCEProjectPackage($sourceBaseDir, $options['project-repo'], $projectTargetDir);
    }

    // Create product package
    $productDir = __DIR__ . '/_tmp_product_' . time();
    if (!file_exists($productDir)) {
        mkdir($productDir, 0777, true);
    }
    $productComposerJson = $productDir . '/composer.json';
    execVerbose(
        'php -f ' . __DIR__
        . '/../../tools/Magento/Tools/Composer/create-root.php -- '
        . '--edition=%s --type=product --source-dir=%s --target-file=%s --wildcard',
        $edition,
        $sourceBaseDir,
        $productComposerJson
    );
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/archiver.php -- '
        . "--dir=$productDir --output=$packagesTargetDir"
    );

    // Create base composer.json
    execVerbose(
        'php -f ' . __DIR__
        . '/../../tools/Magento/Tools/Composer/create-root.php -- '
        . '--edition=%s --type=base --source-dir=%s --target-file=%s',
        $edition,
        $sourceBaseDir,
        $sourceBaseDir . '/composer.json'
    );

    // generate all packages
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/archiver.php -- '
        . "--dir=$sourceBaseDir --output=$packagesTargetDir"
    );
} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}

/**
 * Create CE Project Package
 *
 * @param $sourceBaseDir
 * @param $projectRepo
 * @param $projectTargetDir
 * @throws \Exception
 */
function createCEProjectPackage($sourceBaseDir, $projectRepo, $projectTargetDir)
{
    /**
     * Prepare CE project repository
     */
    $origComposerJson = $sourceBaseDir . '/composer.json';
    $origComposerInfo = json_decode(file_get_contents($origComposerJson));
    $version = $origComposerInfo->version;
    execVerbose("git clone $projectRepo $projectTargetDir");
    $readmeFile = $sourceBaseDir . '/README.md';
    if (is_file($readmeFile)) {
        copy($readmeFile, $projectTargetDir . '/README.md');
    }
    $projectComposerJson = $projectTargetDir . '/composer.json';
    execVerbose(
        'php -f ' . __DIR__
        . '/../../tools/Magento/Tools/Composer/create-root.php -- '
        . '--edition=ce --type=project --source-dir=%s --target-file=%s',
        $sourceBaseDir,
        $projectComposerJson
    );


    // Commit changes to project repository
    $commitMsg = getValidChangelogMessage($sourceBaseDir, $version);
    $gitProjectCmd = sprintf(
        'git --git-dir %s --work-tree %s',
        escapeshellarg("$projectTargetDir/.git"),
        escapeshellarg($projectTargetDir)
    );
    execVerbose("$gitProjectCmd add .");
    execVerbose("$gitProjectCmd config user.name " . getGitUsername());
    execVerbose("$gitProjectCmd config user.email " . getGitEmail());
    execVerbose("$gitProjectCmd commit -m %s", $commitMsg);
}

/**
 * Create EE Project Package
 *
 * @param string $sourceBaseDir
 * @param string $packagesTargetDir
 * @param string $packagesTargetRepo
 * @param string $projectTargetDir
 * @throws \Exception
 */
function createEEProjectPackage($sourceBaseDir, $packagesTargetDir, $packagesTargetRepo, $projectTargetDir)
{
    // Create composer.json
    if (!file_exists($projectTargetDir)) {
        mkdir($projectTargetDir, 0777, true);
    }
    $projectComposerJson = $projectTargetDir . '/composer.json';
    execVerbose(
        'php -f ' . __DIR__
        . '/../../tools/Magento/Tools/Composer/create-root.php -- '
        . '--edition=ee --type=project --source-dir=%s --target-file=%s --repo=%s',
        $sourceBaseDir,
        $projectComposerJson,
        $packagesTargetRepo
    );

    // Archive
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/archiver.php -- '
        . "--dir=$projectTargetDir --output=$packagesTargetDir"
    );
}
