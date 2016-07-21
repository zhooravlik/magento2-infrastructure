<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Visitor;

use PHPSemVerChecker\Visitor\VisitorAbstract;
use PhpParser\Node;

class ApiVisitor extends VisitorAbstract
{

    public function getApiMembers(Node $node)
    {
        $apiCounter = 0;

        if ($this->isApiMember($node)) {
            return $node;
        }

        foreach ($node->stmts as $key => $method) {
            if (!$this->isApiMember($method)) {
                unset($node->stmts[$key]);
            } else {
                $apiCounter++;
            }
        }

        if ($apiCounter === 0) {
            return null;
        }

        return $node;

    }

    public function isApiMember(Node $node)
    {
        $comment = $node->getDocComment();

        return strpos($comment, \Magento\Tools\SemanticVersionChecker\Console\Application::ANNOTATION_API);
    }
}
