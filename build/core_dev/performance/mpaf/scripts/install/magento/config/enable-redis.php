<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

$args = getopt('', ['config_file:', 'cache_host:', 'cache_port:', 'cache_types:', 'session_host::', 'session_port::']);

if (!isset($args['config_file']) || !isset($args['cache_host']) || !isset($args['cache_port']) || !isset($args['cache_types'])) {
    echo <<<END
    Reconfigure magento with new options for redis
    Required parameters:
        --config_file <path to magento env.php>
        --cache_host <redis cache host>
        --cache_port <redis cache port>
        --cache_types <cache types to store in redis>
        --session_host <redis host for session storage - OPTIONAL>
        --session_port <redis port for session storage - OPTIONAL>

    Example:
         php -f scripts/install/magento/config/enable-redis.php  -- --config_file=app/etc/env.php --cache_host=localhost --cache_port=6379 --cache_types=default,page_cache


END;
    exit(1);
}

$config = include $args['config_file'];
if (!is_array($config)) {
    echo "Error reading config";
    exit(1);
}

$cacheTypes = explode(',', $args['cache_types']);
$cacheHost = $args['cache_host'];
$cachePort = $args['cache_port'];
$sessionHost = isset($args['session_host']) ? $args['session_host'] : $cacheHost;
$sessionPort = isset($args['session_port']) ? $args['session_port'] : $cachePort;

foreach ($cacheTypes as $cacheType) {
    $config['cache']['frontend'][$cacheType] =
        [
            'backend' => 'Cm_Cache_Backend_Redis',
            'backend_options' =>
                [
                    'server' => $cacheHost,
                    'port' => $cachePort,
                    'persistent' => '',
                    'database' => 0,
                    'password' => '',
                    'force_standalone' => 0,
                    'connect_retries' => 1,
                    'read_timeout' => 10,
                    'automatic_cleaning_factor' => 0,
                    'compress_data' => 1,
                    'compress_tags' => 1,
                    'compress_threshold' => 20480,
                    'compression_lib' => 'gzip',
                    'use_lua' => 0,
                ],
        ];
}

$config['session'] = [
    'save' => 'redis',
    'save_path' => "tcp://{$sessionHost}:{$sessionPort}?timeout=2.5&database=2",
];

copy($args['config_file'], $args['config_file'] . '.' . time() . 'bak');

file_put_contents($args['config_file'], "<?php \r\nreturn " . var_export($config, true) . ';');