<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mpaf\Tool\Lib\Config;

class Reader
{
    /**
     * @var Converter
     */
    protected $converter;

    public function __construct()
    {
        $this->converter = new Converter();
    }

    public function read()
    {
        $dom = new \DOMDocument();
        $path = BASE_PATH . 'mpaf/tool/etc/config.xml';
        $dom->load($path);
        $dom->xinclude();
        return $this->converter->convert($dom);
    }
}
