<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;

class InterfaceVisitor extends ApiVisitor
{
    /**
     * @param \PhpParser\Node $node
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Interface_) {
            $node = $this->getApiMembers($node);
            if (!is_null($node)) {
                $this->registry->addInterface($node);
            }
        }
    }
}
