#!/usr/bin/php
<?php
/**
 * Script for preparing sandbox package repositories of Magento components and project
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// get CLI options, define variables
define(
'SYNOPSIS',
<<<SYNOPSIS
php -f prepare_sandboxpackages.php --
    --source-dir="<directory>" is required
    --target-packages-repo="<repository>" is required for ee edition
    --updater-repo="<repository>" needed to create project level package
    --updater-branch="<branch>" needed to create project level package
    --composer-project-repository-url="<url>" full http url to the sandbox packages server
    [--edition=<ce|ee>] is optional, default is ce
    [--target-packages-dir="<directory>"] is optional, default is _tmp_packages
    [--target-project-dir="<directory>"] is optional, default is _tmp_project

SYNOPSIS
);
$options = getopt('', [
    'edition:',
    'source-dir:', 'target-packages-dir:', 'target-packages-repo:',
    'updater-repo:', 'updater-branch:', 'composer-project-repository-url:',
    'target-project-dir:'
]);
$edition = isset($options['edition']) ? $options['edition'] : 'ce';
$requiredArgs = $edition === 'ce' ? ['source-dir', 'updater-repo', 'updater-branch', 'composer-project-repository-url'] : ['source-dir', 'target-packages-repo', 'composer-project-repository-url'];
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
$updaterRepo = $options['updater-repo'];
$updaterBranch = isset($options['updater-branch']) ? $options['updater-branch'] : 'master';
$composerProjectRepoUrl = $options['composer-project-repository-url'];

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
                $options['target-packages-repo'],
                $projectTargetDir,
                $composerProjectRepoUrl
            );
            break;
        default:
            createCEProjectPackage(
                $sourceBaseDir,
                $projectTargetDir,
                $packagesTargetDir,
                $updaterRepo,
                $updaterBranch,
                $composerProjectRepoUrl
            );
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

    var_dump($edition, $sourceBaseDir . '/composer.json');

    execVerbose(
        'cat ' . $sourceBaseDir . '/composer.json'
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
 * @param $projectTargetDir
 * @param $packagesTargetDir
 * @param $updaterRepo
 * @param $updaterBranch
 * @param $composerProjectRepoUrl
 */
function createCEProjectPackage($sourceBaseDir, $projectTargetDir, $packagesTargetDir, $updaterRepo, $updaterBranch, $composerProjectRepoUrl)
{
    /**
     * Prepare CE project repository
     */
    if (!file_exists($projectTargetDir)) {
        mkdir($projectTargetDir, 0777, true);
    }
    $projectComposerJson = $projectTargetDir . '/composer.json';
    execVerbose(
        'php -f ' . __DIR__
        . '/../../tools/Magento/Tools/Composer/create-root.php -- '
        . '--edition=ce --type=project --source-dir=%s --target-file=%s --package-repo-url=%s',
        $sourceBaseDir,
        $projectComposerJson,
        $composerProjectRepoUrl
    );

    // Add Updater repo to project
    $updaterDir = $projectTargetDir . '/update';
    if (!file_exists($updaterDir)) {
        mkdir($updaterDir, 0777, true);
    }
    execVerbose('git clone -- %s %s', $updaterRepo, $updaterDir);
    execVerbose('cd %s ; git checkout %s;', $updaterDir, $updaterBranch);

    // Archive
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/archiver.php -- '
        . "--dir=$projectTargetDir --output=$packagesTargetDir"
    );
}

/**
 * Create EE Project Package
 *
 * @param $sourceBaseDir
 * @param $packagesTargetDir
 * @param $packagesTargetRepo
 * @param $projectTargetDir
 * @param $composerProjectRepoUrl
 */
function createEEProjectPackage($sourceBaseDir, $packagesTargetDir, $packagesTargetRepo, $projectTargetDir, $composerProjectRepoUrl)
{
    // Create composer.json
    if (!file_exists($projectTargetDir)) {
        mkdir($projectTargetDir, 0777, true);
    }
    $projectComposerJson = $projectTargetDir . '/composer.json';
    execVerbose(
        'php -f ' . __DIR__
        . '/../../tools/Magento/Tools/Composer/create-root.php -- '
        . '--edition=ee --type=project --source-dir=%s --target-file=%s --repo=%s --package-repo-url=%s',
        $sourceBaseDir,
        $projectComposerJson,
        $packagesTargetRepo,
        $composerProjectRepoUrl
    );

    // Archive
    execVerbose(
        'php -f ' . __DIR__ . '/../../tools/Magento/Tools/Composer/archiver.php -- '
        . "--dir=$projectTargetDir --output=$packagesTargetDir"
    );
}
