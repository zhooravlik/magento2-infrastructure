<?php
/**
 * Magento product edition maker script
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    'USAGE',
    'USAGE: php -f edition.php -- --dir="<working_directory>" --edition="<ce|ee|b2b>" [--internal]'
);
require __DIR__ . '/functions.php';
try {
    $options = getopt('', ['dir:', 'edition:', 'internal']);
    assertCondition(isset($options['dir']), USAGE);
    $dir = $options['dir'];
    assertCondition($dir && is_dir($dir), "The specified directory doesn't exist: {$options['dir']}");
    $dir = rtrim(str_replace('\\', '/', $dir), '/');
    assertCondition(isset($options['edition']), USAGE);

    $lists = ['no-edition.txt'];
    $includeLists = [];

    $baseDir = __DIR__ . '/../../../';
    $isTargetBaseDir = realpath($baseDir) == realpath($dir);
    if (!$isTargetBaseDir) {
        // remove service scripts, if edition tool is run outside of target directory
        $lists[] = 'services.txt';
    } else {
        $includeLists[] = 'services.txt';
    }

    $isInternal = isset($options['internal']) ? true : false;
    if ($isInternal) {
        if ($options['edition'] == 'ee') {
            $includeLists[] = 'internal-ee.txt';
            $list[] = 'internal-b2b.txt';
        }

        if ($options['edition'] == 'b2b') {
            $includeLists[] = 'internal-ee.txt';
            $includeLists[] = 'internal-b2b.txt';
        }
        if ($options['edition'] == 'ce') {
            $list[] = 'internal-ee.txt';
            $list[] = 'internal-b2b.txt';
        }

        $includeLists[] = 'internal.txt';
    } else {
        $lists[] = 'internal.txt';
        $lists[] = 'internal-ee.txt';
        $lists[] = 'internal-b2b.txt';
    }

    $gitCmd = sprintf('git --git-dir %s --work-tree %s', escapeshellarg("{$dir}/.git"), escapeshellarg($dir));
    switch ($options['edition']) {
        case 'ce':
            $lists[] = 'ee.txt';
            copyLicenseToComponents(["$dir/app", "$dir/lib/internal/Magento"]);
            break;
        case 'ee':
            $includeLists[] = 'ee.txt';
            $list[] = 'b2b.txt';
            break;
        case 'b2b':
            $includeLists[] = 'ee.txt';
            $includeLists[] = 'b2b.txt';
            break;
        default:
            throw new Exception("Specified edition '{$options['edition']}' is not implemented.");
    }

    execVerbose("{$gitCmd} add .");

    // remove files that do not belong to edition
    $command = 'php -f ' . __DIR__ . '/../extruder.php -- -v -w ' . escapeshellarg($dir);
    foreach ($lists as $list) {
        $command .= ' -l ' . escapeshellarg(__DIR__ . '/edition/' . $list);
    }
    foreach ($includeLists as $list) {
        $command .= ' -i ' . escapeshellarg(__DIR__ . '/edition/' . $list);
    }
    execVerbose($command, 'Extruder execution failed');

    // root composer.json
    $command = "php -f " . __DIR__ . '/../../tools/Magento/Tools/Composer/create-root.php --'
        . ' --edition=' . $options['edition']
        . ' --source-dir=' . escapeshellarg($dir)
        . ' --target-file=' . escapeshellarg($dir . '/composer.json');
    execVerbose($command);
    execVerbose("{$gitCmd} add composer.json");

    // composer.lock becomes outdated, once the composer.json has changed
    $composerLock = $dir . '/composer.lock';
    if (file_exists($composerLock)) {
        execVerbose("{$gitCmd} rm -f -- composer.lock");
    }
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

/**
 * Copy license files into all published components
 *
 * @param array $directories
 * @return void
 */
function copyLicenseToComponents($directories)
{
    foreach ($directories as $componentsDirectory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($componentsDirectory)) as $fileInfo) {
            $fileName = (string)$fileInfo;
            if (preg_match('/^(.*)composer\.json$/', $fileName, $matches)) {
                $componentDirectory = $matches[1];
                copy(__DIR__ . '/../../../LICENSE.txt', $componentDirectory . 'LICENSE.txt');
                copy(__DIR__ . '/../../../LICENSE_AFL.txt', $componentDirectory . 'LICENSE_AFL.txt');
            }
        }
    }
}

/**
 * A basic assertion
 *
 * @param bool $condition
 * @param string $error
 * @return void
 * @throws \Exception
 */
function assertCondition($condition, $error)
{
    if (!$condition) {
        throw new \Exception($error);
    }
}

/**
 * Copy all files maintaining the directory structure
 *
 * @param string $from
 * @param string $to
 * @return void
 */
function copyAll($from, $to)
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from));
    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $source = $file->getPathname();
            $relative = substr($source, strlen($from));
            $dest = $to . $relative;
            $targetDir = dirname($dest);
            if (!is_dir($targetDir)) {
                echo "Mkdir {$targetDir}\n";
                mkdir($targetDir, 0755, true);
            }
            echo "Copy {$source} to {$dest}\n";
            copy($source, $dest);
        }
    }
}
