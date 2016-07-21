#!/usr/bin/php
<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once realpath(__DIR__ . '/../../app/autoload.php');

define(
    'USAGE',
<<<USAGE
$>./extruder.php -w <working_dir> -l /path/to/list.txt [[-l /path/to/extra.txt] parameters]
    additional parameters:
    -w dir  directory with working copy to edit with the extruder
    -l      one or many files with lists that refer to files and directories to be deleted
    -v      additional verbosity in output

USAGE
);

$options = getopt('w:l:vi:');

$logWriter = new Zend_Log_Writer_Stream('php://output');
$logWriter->setFormatter(new Zend_Log_Formatter_Simple('%message%' . PHP_EOL));
$logger = new Zend_Log($logWriter);

try {
    // working dir argument
    if (empty($options['w'])) {
        throw new Exception(USAGE);
    }
    $workingDir = $options['w'];
    if (!$workingDir || !is_writable($workingDir) || !is_dir($workingDir)) {
        throw new Exception("'{$options['w']}' must be a writable directory.");
    }

    // lists argument
    if (empty($options['l'])) {
        throw new Exception(USAGE);
    }
    if (!is_array($options['l'])) {
        $options['l'] = [$options['l']];
    }
    if (!isset($options['i'])) {
        $options['i'] = [];
    } elseif (!is_array($options['i'])) {
        $options['i'] = [$options['i']];
    }
    $list = [];
    $patternList = [];
    $ignoreList = [];
    foreach ($options['l'] as $file) {
        if (!is_file($file) || !is_readable($file)) {
            throw new Exception("Specified file with patterns does not exist or cannot be read: '{$file}'");
        }
        $patterns = file($file, FILE_IGNORE_NEW_LINES);
        foreach ($patterns as $pattern) {
            if (empty($pattern) || 0 === strpos($pattern, '#')) { // comments start from #
                continue;
            } elseif (0 === strpos($pattern, '~')) { //pattern that must be ignored start from ~
                $pattern = substr($pattern, 1);
                $ignoreList[$pattern] = $workingDir . '/' . $pattern;
            }
            $pattern = $workingDir . '/' . $pattern;
            $patternList[$pattern] = $pattern;
        }
    }
    foreach ($options['i'] as $file) {
        if (!is_file($file) || !is_readable($file)) {
            throw new Exception("Specified file with ignore patterns does not exist or cannot be read: '{$file}'");
        }
        $patterns = file($file, FILE_IGNORE_NEW_LINES);
        foreach ($patterns as $pattern) {
            if (empty($pattern) || 0 === strpos($pattern, '#')) { // comments start from #
                continue;
            }
            $pattern = $workingDir . '/' . $pattern;
            $ignoreList[$pattern] = $pattern;
        }
    }

    foreach ($patternList as $pattern) {
        $items = glob($pattern, GLOB_BRACE);
        if (empty($items)) {
            throw new Exception("glob() pattern '{$pattern}' returned empty result.");
        }
        $list = array_merge($list, $items);
    }
    $ignore = [];
    foreach ($ignoreList as $pattern) {
        $items = glob($pattern, GLOB_BRACE);
        if (empty($items)) {
            throw new Exception("glob() pattern '{$pattern}' returned empty result.");
        }
        $ignore = array_merge($ignore, $items);
    }
    $list = array_diff($list, $ignore);
    if (empty($list)) {
        throw new Exception('List of files or directories to delete is empty.');
    }
    // avoid multiple attempts to remove same file, including removal of a directory before removal of files in it
    $list = array_unique($list);
    rsort($list);

    // verbosity argument
    $verbose = isset($options['v']);

    // perform "extrusion"
    $shell = new \Magento\Framework\Shell(new \Magento\Framework\Shell\CommandRenderer(), $verbose ? $logger : null);
    foreach ($list as $item) {
        if (!file_exists($item)) {
            throw new Exception("The file or directory '{$item} is marked for deletion, but it doesn't exist.");
        }
        $itemRel = substr($item, strlen($workingDir) + 1);
        $shell->execute(
            'git --git-dir %s --work-tree %s rm -r -f -- %s',
            ["{$workingDir}/.git", $workingDir, $itemRel]
        );
        if (file_exists($item)) {
            throw new Exception("The file or directory '{$item}' was supposed to be deleted, but it still exists.");
        }
    }

    exit(0);
} catch (Exception $e) {
    if ($e->getPrevious()) {
        $message = (string)$e->getPrevious();
    } else {
        $message = $e->getMessage();
    }
    $logger->log($message, Zend_Log::ERR);
    exit(1);
}
