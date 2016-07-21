<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Operation;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use PHPSemVerChecker\SemanticVersioning\Level;
use Magento\Tools\SemanticVersionChecker\Node\Statement\ClassConstant;

class ClassConstantAdded extends ClassConstantOperation
{
    /**
     * @var string
     */
    protected $code = [
        'class'     => 'M071',
        'interface' => 'M072',
    ];

    /**
     * @var int
     */
    protected $level = [
        'class'     => Level::MAJOR,
        'interface' => Level::MAJOR,
    ];
    /**
     * @var string
     */
    protected $reason = 'Constant has been added.';
    /**
     * @var string
     */
    protected $fileAfter;
    /**
     * @var \PhpParser\Node\Stmt\ClassConst
     */
    protected $constantAfter;

    /**
     * @var \PhpParser\Node\Stmt
     */
    protected $contextAfter;

    /**
     * @param string                            $context
     * @param string                            $fileAfter
     * @param \PhpParser\Node\Stmt\ClassConst   $constantAfter
     * @param \PhpParser\Node\Stmt              $contextAfter
     */
    public function __construct($context, $fileAfter, ClassConst $constantAfter, Stmt $contextAfter)
    {
        $this->fileAfter = $fileAfter;
        $this->constantAfter = $constantAfter;
        $this->contextAfter = $contextAfter;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->fileAfter;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->constantAfter->getLine();
    }

    /**
     * @return string
     */
    public function getTarget()
    {
       return ClassConstant::getFullyQualifiedName($this->contextAfter, $this->constantAfter);
    }
}
