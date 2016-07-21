<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Console\Command;

use Magento\Tools\SemanticVersionChecker\Scanner\Scanner;
use Magento\Tools\SemanticVersionChecker\FileIterator\FileIterator;
use Magento\Tools\SemanticVersionChecker\Analyzer\Analyzer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use PHPSemVerChecker\Reporter\Reporter;

class CompareSourceCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('compare')
            ->setDescription('Compare a set of files to determine what semantic versioning change needs to be done')
            ->setDefinition([
                new InputArgument('source-before', InputArgument::REQUIRED, 'A directory to check'),
                new InputArgument('source-after', InputArgument::REQUIRED, 'A directory to check against'),
                new InputOption('full-path', null, InputOption::VALUE_NONE, 'Display the full path to the file instead of the relative path'),
            ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        $fileIterator = new FileIterator();
        $scannerBefore = new Scanner();
        $scannerAfter = new Scanner();

        $sourceBefore = $input->getArgument('source-before');
        $sourceBefore = $fileIterator->getFilesAsArray($sourceBefore, '.php');

        $sourceAfter = $input->getArgument('source-after');
        $sourceAfter = $fileIterator->getFilesAsArray($sourceAfter, '.php');

        foreach ($sourceBefore as $file) {
            $scannerBefore->scan($file);
        }

        foreach ($sourceAfter as $file) {
            $scannerAfter->scan($file);
        }

        $registryBefore = $scannerBefore->getRegistry();
        $registryAfter = $scannerAfter->getRegistry();

        $analyzer = new Analyzer();
        $report = $analyzer->analyze($registryBefore, $registryAfter);

        $reporter = new Reporter($report, $input);
        $output = new StreamOutput(fopen('svc.log', 'w+'));
        $reporter->output($output);

        $duration = microtime(true) - $startTime;
        $output->writeln('');
        $output->writeln('Time: ' . round($duration, 3) . ' seconds, Memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . ' MB');
    }
}
