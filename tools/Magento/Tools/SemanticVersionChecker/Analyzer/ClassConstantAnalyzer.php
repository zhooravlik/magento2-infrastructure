<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Analyzer;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use Magento\Tools\SemanticVersionChecker\Operation\ClassConstantAdded;
use Magento\Tools\SemanticVersionChecker\Operation\ClassConstantRemoved;
use PHPSemVerChecker\Report\Report;

class ClassConstantAnalyzer 
{
    /**
     * @var string
     */
    protected $context;
    /**
     * @var null|string
     */
    protected $fileBefore;
    /**
     * @var null|string
     */
    protected $fileAfter;

    /**
     * @param string $context
     * @param string $fileBefore
     * @param string $fileAfter
     */
    public function __construct($context, $fileBefore = null, $fileAfter = null)
    {
        $this->context = $context;
        $this->fileBefore = $fileBefore;
        $this->fileAfter = $fileAfter;
    }

    public function analyze(Stmt $contextBefore, Stmt $contextAfter)
    {
        $report = new Report();

        $constantsBefore = $this->getConstants($contextBefore);
        $constantsAfter = $this->getConstants($contextAfter);

        $constantsBeforeKeyed = [];
        foreach ($constantsBefore as $constant) {
            $constantsBeforeKeyed[$this->getName($constant)] = $constant;
        }

        $constantsAfterKeyed = [];
        foreach ($constantsAfter as $constant) {
            $constantsAfterKeyed[$this->getName($constant)] = $constant;
        }

        $constantNamesBefore = array_keys($constantsBeforeKeyed);
        $constantNamesAfter = array_keys($constantsAfterKeyed);
        $constantsAdded = array_diff($constantNamesAfter, $constantNamesBefore);
        $constantsRemoved = array_diff($constantNamesBefore, $constantNamesAfter);

        foreach ($constantsRemoved as $constant) {
            $constantBefore = $constantsBeforeKeyed[$constant];
            $data = new ClassConstantRemoved($this->context, $this->fileAfter, $constantBefore, $contextBefore);
            $report->add($this->context, $data);
        }

        foreach ($constantsAdded as $constant) {
            $constantAfter = $constantsAfterKeyed[$constant];
            $data = new ClassConstantAdded($this->context, $this->fileAfter, $constantAfter, $contextAfter);
            $report->add($this->context, $data);
        }

        return $report;
    }

    protected function getConstants(Stmt $context)
    {
        $constants = [];
        foreach ($context->stmts as $stmt) {
            if ($stmt instanceof ClassConst) {
                $constants[] = $stmt;
            }
        }
        return $constants;
    }

    protected function getName(ClassConst $constant)
    {
        return $constant->consts[0]->name;
    }
}
