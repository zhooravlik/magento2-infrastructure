<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tools\SemanticVersionChecker\Console;

use Magento\Tools\SemanticVersionChecker\Console\Command\CompareSourceCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{

    const ANNOTATION_API = '@api';

    public function __construct()
    {
        parent::__construct('Semantic Versioning Checker by x.commerce');
    }

    /**
     * @return array|\Symfony\Component\Console\Command\Command[]
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = $this->add(new CompareSourceCommand());
        return $commands;
    }
}
