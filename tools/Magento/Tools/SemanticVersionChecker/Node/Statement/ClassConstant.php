<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Node\Statement;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst as BaseClassConstant;

class ClassConstant extends BaseClassConstant
{
    public static function getFullyQualifiedName(Stmt $context, BaseClassConstant $constant)
    {
        $fqcn = $context->name;
        if ($context->namespacedName) {
            $fqcn = $context->namespacedName->toString();
        }
        return $fqcn . '::' . $constant->consts[0]->name;
    }

}
