<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mpaf\Tool\Lib;

use Mpaf\Tool\Lib\Config\Reader;

class Config
{
    protected $data;

    /**
     * @var Reader
     */
    protected $reader;

    public function __construct()
    {
        $this->reader = new Reader();
        $this->data = $this->reader->read();
    }

    public function getScenario($name)
    {
        if (!isset($this->data['scenario'][$name])) {
            throw new \InvalidArgumentException('Invalid scenario: ' . $name);
        }
        return $this->data['scenario'][$name];
    }

    public function getScenarioList()
    {
        return array_keys($this->data['scenario']);
    }

    public function getVariables()
    {
        return $this->data['variables'];
    }
}
