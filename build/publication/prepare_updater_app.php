#!/usr/bin/php
<?php
/**
 * Script for preparing updater application repository as part of  Magento 2 Community Edition package
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// get CLI options, define variables
define(
    'SYNOPSIS',
<<<SYNOPSIS
php -f prepare_updater_app.php --
    --source-dir="<directory>"
    --updater-repo="<repository>"
    [--updater-branch="<branch>"]
    --project-repo="<repository>"
    --target-project-dir="<directory>"
SYNOPSIS
);
$options = getopt('', [
    'source-dir:', 'updater-repo:', 'updater-branch::', 'project-repo:', 'target-project-dir:'
]);
$requiredArgs = ['source-dir', 'updater-repo', 'project-repo', 'target-project-dir'];
foreach ($requiredArgs as $arg) {
    if (empty($options[$arg])) {
        echo SYNOPSIS;
        exit(1);
    }
}

require_once __DIR__ . '/functions.php';

$sourceDir = $options['source-dir'];
$projectTargetDir = $options['target-project-dir'];
$projectRepo = $options['project-repo'];
$updaterRepo = $options['updater-repo'];
$updaterBranch = isset($options['updater-branch']) ? $options['updater-branch'] : 'master';
$updaterRepoName = 'updater-app';
$updaterDir = 'update';

try {
    /**
     * Prepare base to work with and to not spoil original repository
     */
    $sourceBaseDir = __DIR__ . '/_tmp_base_source';
    if (!file_exists($sourceBaseDir)) {
        execVerbose("git clone %s %s", $sourceDir, $sourceBaseDir);
    }
    $origComposerJson = $sourceDir . '/composer.json';
    $origComposerInfo = json_decode(file_get_contents($origComposerJson));
    $version = $origComposerInfo->version;

    if (!file_exists($projectTargetDir)) {
        execVerbose("git clone $projectRepo $projectTargetDir");
    }

    // Merge updater application into the product repo
    $gitCmd = sprintf(
        'git --git-dir %s --work-tree %s',
        escapeshellarg("$projectTargetDir/.git"),
        escapeshellarg($projectTargetDir)
    );

    execVerbose("$gitCmd config user.name " . getGitUsername());
    execVerbose("$gitCmd config user.email " . getGitEmail());
    execVerbose("$gitCmd remote add -f $updaterRepoName $updaterRepo");
    if (file_exists($projectTargetDir. '/' . $updaterDir )) {
        execVerbose("$gitCmd checkout {$updaterRepoName}/$updaterBranch");
        execVerbose("$gitCmd fetch $updaterRepoName");
        execVerbose("$gitCmd checkout $updaterBranch");
        execVerbose("$gitCmd merge --squash -s subtree -X theirs --no-commit {$updaterRepoName}/$updaterBranch");
    } else {
        execVerbose("$gitCmd merge -s ours --no-commit {$updaterRepoName}/$updaterBranch");
        execVerbose("$gitCmd read-tree --prefix=$updaterDir -u {$updaterRepoName}/$updaterBranch");
    }
    // Commit changes to product repository
    $commitMsg = getValidChangelogMessage($sourceBaseDir, $version);
    $result = execVerbose("$gitCmd diff-index --quiet HEAD || $gitCmd commit -m %s", $commitMsg);
} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
