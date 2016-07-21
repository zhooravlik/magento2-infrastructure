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
    'repo:',
    'target-repo:'
]);
$requiredArgs = ['repo', 'target-repo'];
foreach ($requiredArgs as $arg) {
    if (empty($options[$arg])) {
        echo SYNOPSIS;
        exit(1);
    }
}

require_once __DIR__ . '/../publication/functions.php';

$edition = (isset($options['edition']) && 'ee' == $options['edition']) ? 'ee' : 'ce';
$repo = $options['repo'];
$targetRepo = $options['target-repo'];

$targetRepoDir = 'target_repo';

try {
    // clone migration tool
//    execVerbose("git clone -- %s %s", $repo, $repoDir);
    execVerbose('git clone -- %s %s', $targetRepo, $targetRepoDir);
    $gitCmd = sprintf(
        'git --git-dir %s --work-tree %s',
        escapeshellarg("$targetRepoDir/.git"),
        escapeshellarg($targetRepoDir)
    );
    execVerbose("$gitCmd config user.name " . getGitUsername());
    execVerbose("$gitCmd config user.email " . getGitEmail());
    execVerbose("$gitCmd remote add internal_repo $repo");
    execVerbose("$gitCmd fetch internal_repo");

    foreach (['master'] as $branch) {
        execVerbose("$gitCmd checkout -b internal_$branch internal_repo/$branch");
        execVerbose("$gitCmd checkout $branch");
        execVerbose("$gitCmd reset --hard origin/$branch");
        execVerbose("$gitCmd rm -rf ./");
        execVerbose("cd $targetRepoDir && git read-tree -v -m -u internal_$branch && cd ..");
        execVerbose(
            'php -f ' . __DIR__ . '/prepare_edition.php -- --keep-git --edition=%s --source-dir=%s',
            $edition, $targetRepoDir
        );
        if ($edition == 'ee') {
            execVerbose("cd $targetRepoDir && mv README_EE.md README.md && cd ..");
        }
        execVerbose("$gitCmd add --all .");
        $baseComposerInfo = json_decode(file_get_contents($targetRepoDir . '/composer.json'));
        $version = $baseComposerInfo->version;
        execVerbose("$gitCmd commit -m'Updated Data Migration Tool(version $version)'");
        execVerbose("$gitCmd tag $version");
    }
} catch (\Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    exit(1);
}
