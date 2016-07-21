<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Operation;

use PHPSemVerChecker\Operation\Operation;

abstract class ClassConstantOperation extends Operation
{
    /**
     * @var string
     */
    protected $context;

    public function getCode()
    {
        return $this->code[$this->context];
    }

    public function getLevel()
    {
        return $this->level[$this->context];
    }
}
