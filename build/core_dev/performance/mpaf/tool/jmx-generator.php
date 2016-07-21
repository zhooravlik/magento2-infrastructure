<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

spl_autoload_register('autoloader');
define('BASE_PATH', $basePath = realpath(__DIR__ . '/../../') . '/');
$defaultOutputDirectory = BASE_PATH . 'mpaf/tool/';
function autoloader($class)
{
    $classPath = str_replace('Mpaf\\Tool\\Lib\\', 'mpaf\\tool\\lib\\', $class);
    $classPath = str_replace('\\', '/', $classPath);
    $path =  BASE_PATH . $classPath . '.php';
    include $path;
}

$args = getopt('s::d::', ['scenario::', 'dir::']);
$testScriptName = isset($args['scenario']) ? $args['scenario'] : null;
$outputDirectory = isset($args['dir']) ? $args['dir'] : $defaultOutputDirectory;

$config = new \Mpaf\Tool\Lib\Config();

if (empty($args)) {
    echo ('Magento JMX script generator' . PHP_EOL . PHP_EOL
    . 'Usage:' . PHP_EOL
    . '   php ' . str_replace(__DIR__ . '/', '', __FILE__)
    . ' --scenario="benchmark" [--dir="/path/to/output/dir/"]' . PHP_EOL . PHP_EOL
    . 'Parameters:' . PHP_EOL
    . '   --scenario   - Test scenario name. Default: generate all. Available values: '
        . implode(', ', array_merge(['all'], $config->getScenarioList())) . PHP_EOL
    . '   --dir        - Output directory name. Default: ' . $defaultOutputDirectory . PHP_EOL
    );
    exit(0);
}

$builder = new \Mpaf\Tool\Lib\Builder($config, $outputDirectory);
$scriptToGenerate = [];
if ($testScriptName == 'all') {
    $scriptToGenerate = $config->getScenarioList();
} else {
    $scriptToGenerate[] = $testScriptName;
}
try {
    foreach ($scriptToGenerate as $scenario) {
        $path = $builder->build($scenario);
        echo 'Generated scenario [' . $scenario . ']: ' . $path . PHP_EOL;
    }
} catch (\Exception $e) {
    echo $e->getMessage();
    exit(255);
}

