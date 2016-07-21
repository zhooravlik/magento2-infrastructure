<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Tools\Composer\Helper\ExcludeFilter;
use Magento\Tools\Composer\Package\Reader;

require __DIR__ . '/../../../bootstrap_tools.php';
$destinationDir = __DIR__ . '/_skeleton';

/**
 * Skeleton Composer Package Creator Tool
 *
 * This tool creates a skeleton composer package
 */
try {
    $opt = new \Zend_Console_Getopt(
        [
            'source|s=s' => 'Source directory. Default value ' . realpath(BP),
            'destination|d=s' => 'Destination directory. Default value ' . $destinationDir,
        ]
    );
    $opt->parse();

    $sourceDir = $opt->getOption('s') ?: realpath(BP);
    $sourceDir = str_replace('\\', '/', realpath($sourceDir));
    if (!$sourceDir || !is_dir($sourceDir)) {
        throw new Exception($opt->getOption('s') . " must be a Magento code base.");
    }

    $destinationDir = $opt->getOption('d') ?: $destinationDir;
    $destinationDir = str_replace('\\', '/', $destinationDir);

    $logWriter = new \Zend_Log_Writer_Stream('php://output');
    $logWriter->setFormatter(new \Zend_Log_Formatter_Simple('[%timestamp%] : %message%' . PHP_EOL));
    $logger = new Zend_Log($logWriter);
    $logger->setTimestampFormat("H:i:s");
    $logger->info(sprintf("Your Magento installation directory (Source Directory): %s ", $sourceDir));
    $logger->info(sprintf("Your skeleton output directory (Destination Directory): %s ", $destinationDir));

    try {
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }
    } catch (\Exception $ex) {
        $logger->error(sprintf("ERROR: Creating Directory %s failed. Message: %s", $destinationDir, $ex->getMessage()));
        exit($e->getCode());
    }

    $reader = new Reader($sourceDir);
    $excludes = $reader->getExcludePaths();
    $excludes[] = $destinationDir;

    $directory = new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS);
    $directory = new ExcludeFilter($directory, $excludes);
    $files = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
    foreach ($files as $file) {
        $file = str_replace('\\', '/', realpath($file));
        $relativePath = str_replace($sourceDir . '/', '', $file);
        if (is_dir($file) === true) {
            $relativePath .= '/';
            mkdir($destinationDir . '/' . $relativePath);
        } elseif (is_file($file) === true) {
            copy($file, $destinationDir . '/' . $relativePath);
        } else {
            throw new \Exception("The path $file is not a directory or file!", "1");
        }
    }

    $logger->info(
        sprintf(
            "SUCCESS: Skeleton created! You should be able to find it at %s \n",
            $destinationDir
        )
    );
} catch (\Zend_Console_Getopt_Exception $e) {
    echo $e->getUsageMessage();
    exit(1);
} catch (\Exception $e) {
    echo $e;
    exit(1);
}
