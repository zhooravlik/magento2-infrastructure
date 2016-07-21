<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Analyzer;

use PHPSemVerChecker\Operation\ClassAdded;
use PHPSemVerChecker\Operation\ClassRemoved;
use PHPSemVerChecker\Registry\Registry;
use PHPSemVerChecker\Report\Report;
use PHPSemVerChecker\Analyzer\ClassMethodAnalyzer;
use PHPSemVerChecker\Analyzer\PropertyAnalyzer;

class ClassAnalyzer 
{
    protected $context = 'class';

    public function analyze(Registry $registryBefore, Registry $registryAfter)
    {
        $report = new Report();

        $keysBefore = array_keys($registryBefore->data['class']);
        $keysAfter = array_keys($registryAfter->data['class']);
        $added = array_diff($keysAfter, $keysBefore);
        $removed = array_diff($keysBefore, $keysAfter);
        $toVerify = array_intersect($keysBefore, $keysAfter);

        foreach ($removed as $key) {
            $fileBefore = $registryBefore->mapping['class'][$key];
            $classBefore = $registryBefore->data['class'][$key];

            $data = new ClassRemoved($fileBefore, $classBefore);
            $report->addClass($data);
        }

        foreach ($toVerify as $key) {
            $fileBefore = $registryBefore->mapping['class'][$key];
            /** @var \PhpParser\Node\Stmt\Class_ $classBefore */
            $classBefore = $registryBefore->data['class'][$key];
            $fileAfter = $registryAfter->mapping['class'][$key];
            /** @var \PhpParser\Node\Stmt\Class_ $classBefore */
            $classAfter = $registryAfter->data['class'][$key];

            // Leave non-strict comparison here
            if ($classBefore != $classAfter) {
                $analyzers = [
                    new ClassMethodAnalyzer('class', $fileBefore, $fileAfter),
                    new PropertyAnalyzer('class', $fileBefore, $fileAfter),
                    new ClassConstantAnalyzer('class', $fileBefore, $fileAfter),
                ];

                foreach ($analyzers as $analyzer) {
                    $internalReport = $analyzer->analyze($classBefore, $classAfter);
                    $report->merge($internalReport);
                }
            }
        }

        foreach ($added as $key) {
            $fileAfter = $registryAfter->mapping['class'][$key];
            $classAfter = $registryAfter->data['class'][$key];

            $data = new ClassAdded($fileAfter, $classAfter);
            $report->addClass($data);
        }

        return $report;
    }
}
