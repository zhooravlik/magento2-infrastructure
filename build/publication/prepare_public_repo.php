#!/usr/bin/php
<?php
/**
 * Script for preparing repository for publication a release
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */

// get CLI options, define variables
define(
    'SYNOPSIS',
<<<SYNOPSIS
php -f prepare_public_repo.php --
    --source-dir="<directory>" Path to internal repository ready for publication
    --public-repo="<repository>" URL of public repository
    --local-public-repo-name="<name>" Local name used for the public remote.
        Also used as a prefix for local branch names
    --branches="<branch>[,<branch>]" List of branches to prepare
SYNOPSIS
);
$options = getopt(
    '',
    ['source-dir:', 'public-repo:', 'local-public-repo-name:', 'branches:']
);
$requiredArgs = ['source-dir', 'public-repo', 'local-public-repo-name', 'branches'];
foreach ($requiredArgs as $arg) {
    if (empty($options[$arg])) {
        echo SYNOPSIS;
        exit(1);
    }
}

require_once __DIR__ . '/functions.php';

$sourceDir = $options['source-dir'];

try {
    $baseComposerInfo = json_decode(file_get_contents($sourceDir . '/composer.json'));
    $version = $baseComposerInfo->version;

    // Validate Changelog
    getValidChangelogMessage($sourceDir, $version);

    // Clone public repository
    $gitCmd = sprintf(
        'git --git-dir %s --work-tree %s',
        escapeshellarg("$sourceDir/.git"),
        escapeshellarg($sourceDir)
    );
    execVerbose("$gitCmd config user.name " . getGitUsername());
    execVerbose("$gitCmd config user.email " . getGitEmail());

    list($internalBranch) = execVerbose("$gitCmd symbolic-ref HEAD | sed -e \"s/^refs\\/heads\\///\"");

    $publicRepo = $options['public-repo'];
    $localPublicRepoName = $options['local-public-repo-name'];
    execVerbose("$gitCmd remote add $localPublicRepoName $publicRepo");
    execVerbose("$gitCmd fetch $localPublicRepoName");

    // Merge internal to requested branches
    $branches = explode(',', $options['branches']);
    if (!$branches) {
        throw new \UnexpectedValueException("'branches' can't be empty. Branches should be separated by ','");
    }
    foreach ($branches as $branch) {
        $branch = trim($branch);
        execVerbose("$gitCmd checkout -b $localPublicRepoName-$branch $localPublicRepoName/$branch");
        execVerbose("$gitCmd merge --ff-only $internalBranch");
    }
} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
