<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once __DIR__ . '/../../tools/Magento/Tools/Composer/Package/Version.php';

/**
 * Execute a command with automatic escaping of arguments
 *
 * @param string $command
 * @return array
 * @throws Exception
 */
function execVerbose($command)
{
    $args = func_get_args();
    $args = array_map('escapeshellarg', $args);
    $args[0] = $command;
    $command = call_user_func_array('sprintf', $args);
    echo $command . PHP_EOL;
    exec($command, $output, $exitCode);
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }
    if (0 !== $exitCode) {
        throw new Exception("Command has failed with exit code: $exitCode.");
    }
    return $output;
}

/**
 * Get the top section of a text in markdown format
 *
 * @param string $contents
 * @return string
 * @throws Exception
 * @link http://daringfireball.net/projects/markdown/syntax
 */
function getTopMarkdownSection($contents)
{
    $parts = preg_split('/^[=\-]+\s*$/m', $contents);
    if (!isset($parts[1])) {
        throw new Exception("No commit message found in the changelog file.");
    }
    list($version, $body) = $parts;
    $version = trim($version);
    \Magento\Tools\Composer\Package\Version::validate($version);
    $body = explode("\n", trim($body));
    if (count($parts) > 2) {
        array_pop($body);
    }
    $body = implode("\n", $body);
    return $version . "\n" . $body;
}

/**
 * Get Magento user name for public GitHub repository
 *
 * @return string
 */
function getGitUsername()
{
    return 'mage2-team';
}

/**
 * Get Magento user e-mail for public GitHub repository
 *
 * @return string
 */
function getGitEmail()
{
    return 'mage2-team@magento.com';
}

/**
 * Validate top Changelog message and return it, if valid
 *
 * @param string $dir
 * @param string $version
 * @return string
 * @throws Exception
 */
function getValidChangelogMessage($dir, $version)
{
    $changelogFile = $dir . '/CHANGELOG.md';
    if (!file_exists($changelogFile)) {
        throw new \UnexpectedValueException("Changelog file '$changelogFile' does not exist");
    }
    echo "Source changelog file is '$changelogFile'" . PHP_EOL;
    $sourceLog = file_get_contents($changelogFile);
    $topMessage = trim(getTopMarkdownSection($sourceLog));
    if (!preg_match('#^' . preg_quote($version) . '\n#', $topMessage)) {
        throw new \UnexpectedValueException(
            "Version on top of Changelog doesn't correspond to the release version '$version'"
        );
    }

    return $topMessage;
}
