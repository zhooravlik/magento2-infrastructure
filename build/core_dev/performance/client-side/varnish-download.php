<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Console\Response;
use Magento\Framework\AppInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\StoreManager;

require_once dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/app/bootstrap.php';

class VarnishDownloadApp implements AppInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $savePath;

    /**
     * @var integer
     */
    private $version;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $savePath
     * @param string $version
     */
    public function __construct(ObjectManagerInterface $objectManager, $savePath, $version)
    {
        $this->objectManager = $objectManager;
        $this->savePath = $savePath;
        $this->version = $version;
    }

    /**
     * Launch application
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function launch()
    {
        $response = new Response();

        /** @var Config $config */
        $config = $this->objectManager->create('\Magento\PageCache\Model\Config');

        if ($this->version != null && $this->version == 3) {
            $content = $config->getVclFile(Config::VARNISH_3_CONFIGURATION_PATH);
        }
        else {
            $content = $config->getVclFile(Config::VARNISH_4_CONFIGURATION_PATH);
        }
        $response->setBody($content);

        if ($this->savePath) {
            file_put_contents($this->savePath, $content);
        }
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function catchException(Bootstrap $bootstrap, \Exception $exception)
    {
        return false;
    }
}

$args = getopt('o:v:');

$params = $_SERVER;
$params[StoreManager::PARAM_RUN_CODE] = 'admin';
$params[StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = Bootstrap::create(BP, $params);
$app = new \VarnishDownloadApp($bootstrap->getObjectManager(), isset($args['o']) ? $args['o'] : null, isset($args['v']) ? $args['v'] : null);
$bootstrap->run($app);
