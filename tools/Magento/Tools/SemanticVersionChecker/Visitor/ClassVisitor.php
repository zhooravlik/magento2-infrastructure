<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_ as BaseClass;

class ClassVisitor extends ApiVisitor
{

    /**
     * @param Node $node
     * @param InputInterface $input
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof BaseClass) {
            $node = $this->getApiMembers($node);
            if (!is_null($node)) {
                $this->registry->addClass($node);
            }
        }
    }

}
