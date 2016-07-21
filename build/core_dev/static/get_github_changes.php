<?php

/**
 * Script to get changes between feature branch and the mainline
 *
 * @category   dev
 * @package    build
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    'USAGE',
<<<USAGE
    php -f get_github_changes.php --
    --output-file="<output_file>"
    --base-path="<base_path>"
    --edition-code="<edition_code>"
    [--file-formats="<comma_separated_list_of_formats>"]

USAGE
);

$options = getopt('', ['output-file:', 'base-path:', 'edition-code:', 'file-formats:']);

$requiredOptions = ['output-file', 'edition-code', 'base-path'];
if (!validateInput($options, $requiredOptions)) {
    echo USAGE;
    exit(1);
}

$fileFormats = explode(',', isset($options['file-formats']) ? $options['file-formats'] : 'php');

$changes = retrieveChangesAcrossForks($options);
$changedFiles = getChangedFiles($changes, $fileFormats);
generateChangedFilesList($options['output-file'], $changedFiles);

/**
 * Generates a file containing changed files
 *
 * @param string $outputFile
 * @param array $changedFiles
 * @return void
 */
function generateChangedFilesList($outputFile, $changedFiles)
{
    $changedFilesList = fopen($outputFile, 'w');
    foreach ($changedFiles as $file) {
        fwrite($changedFilesList, $file . PHP_EOL);
    }
    fclose($changedFilesList);
}

/**
 * Gets list of changed files
 *
 * @param array $changes
 * @param array $fileFormats
 * @return array
 */
function getChangedFiles($changes, $fileFormats)
{
    $files = [];
    foreach ($changes as $fileName) {
        foreach ($fileFormats as $format) {
            $isFileFormat = strpos($fileName, '.' . $format);
            if ($isFileFormat) {
                $files[] = $fileName;
            }
        }
    }

    return $files;
}

/**
 * Retrieves changes across forks
 *
 * @param array $options
 * @return array
 * @throws Exception
 */
function retrieveChangesAcrossForks($options)
{
    $mainlineRemoteUrl = 'git@github.corp.magento.com:magento2/magento2' . $options['edition-code'] . '.git';
    $mainline = 'mainline';

    $repo = new GitRepo($options['base-path'] . $options['edition-code']);
    $repo->addRemote($mainline, $mainlineRemoteUrl);
    $repo->fetch($mainline);
    return $repo->compareChanges($mainline, 'develop');
}

/**
 * Validates input options based on required options
 *
 * @param array $options
 * @param array $requiredOptions
 * @return bool
 */
function validateInput(array $options, array $requiredOptions)
{
    foreach ($requiredOptions as $requiredOption) {
        if (!isset($options[$requiredOption]) || empty($options[$requiredOption])) {
            return false;
        }
    }
    return true;
}


class GitRepo
{
    /**
     * Absolute path to git project
     *
     * @var string
     */
    private $workTree;

    /**
     * @var array
     */
    private $remoteList = [];

    /**
     * @param string $workTree absolute path to git project
     */
    public function __construct($workTree)
    {
        if (empty($workTree) || !is_dir($workTree)) {
            throw new UnexpectedValueException('Working tree should be a valid path to directory');
        }
        $this->workTree = $workTree;
    }

    /**
     * Adds remote
     *
     * @param string $alias
     * @param string $url
     */
    public function addRemote($alias, $url)
    {
        if (isset($this->remoteList[$alias])) {
            return;
        }
        $this->remoteList[$alias] = $url;

        $this->call(sprintf('remote add %s %s', $alias, $url));
    }

    /**
     * Fetches remote
     *
     * @param string $remoteAlias
     */
    public function fetch($remoteAlias)
    {
        if (!isset($this->remoteList[$remoteAlias])) {
            throw new LogicException('Alias is not defined');
        }

        $this->call(sprintf('fetch %s', $remoteAlias));
    }

    /**
     * Returns files changes between branch
     *
     * @param string $remoteAlias
     * @param string $remoteBranch
     * @return array
     */
    public function compareChanges($remoteAlias, $remoteBranch)
    {
        if (!isset($this->remoteList[$remoteAlias])) {
            throw new LogicException('Alias is not defined');
        }

        $result = $this->call(sprintf('log %s/%s..HEAD  --name-status --oneline', $remoteAlias, $remoteBranch));

        return is_array($result)
            ? $this->filterChangedBackFiles(
                $this->filterChangedFiles($result),
                $remoteAlias,
                $remoteBranch
            )
            : [];
    }

    /**
     * Filters git cli output for changed files
     *
     * @param array $changes
     * @return array
     */
    protected function filterChangedFiles(array $changes)
    {
        $changedFilesMasks = [
            'M' => "M\t",
            'A' => "A\t"
        ];
        $filteredChanges = [];
        foreach ($changes as $fileName) {
            foreach ($changedFilesMasks as $mask) {
                if (strpos($fileName, $mask) === 0) {
                    $fileName = str_replace($mask, '', $fileName);
                    $fileName = trim($fileName);
                    if (!in_array($fileName, $filteredChanges) && is_file($this->workTree . '/' . $fileName)) {
                        $filteredChanges[] = $fileName;
                    }
                    break;
                }
            }
        }
        return $filteredChanges;
    }

    /**
     * Makes a diff of file for specified remote/branch and filters only those have real changes
     *
     * @param array $changes
     * @param string $remoteAlias
     * @param string $remoteBranch
     * @return array
     */
    protected function filterChangedBackFiles(array $changes, $remoteAlias, $remoteBranch)
    {
        $filteredChanges = [];
        foreach ($changes as $fileName) {
            $result = $this->call(sprintf('diff %s/%s -- %s', $remoteAlias, $remoteBranch, $fileName));
            if ($result) {
                $filteredChanges[] = $fileName;
            }
        }

        return $filteredChanges;
    }

    /**
     * Makes call ro git cli
     *
     * @param string $command
     * @return mixed
     */
    private function call($command)
    {
        $gitCmd = sprintf(
            'git --git-dir %s --work-tree %s',
            escapeshellarg("{$this->workTree}/.git"),
            escapeshellarg($this->workTree)
        );

        exec(escapeshellcmd(sprintf('%s %s', $gitCmd, $command)), $output);
        return $output;
    }
}
