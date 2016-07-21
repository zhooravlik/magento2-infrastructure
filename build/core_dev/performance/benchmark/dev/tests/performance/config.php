<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
return [
    'application' => [
        'url_host' => '{{web_access_host}}',
        'url_path' => '{{web_access_path}}',
        'installation' => [
            'options' => [
                'language'                   => 'en_US',
                'timezone'                   => 'America/Los_Angeles',
                'currency'                   => 'USD',
                'db-host'                    => '{{db-host}}',
                'db-name'                    => '{{db-name}}',
                'db-user'                    => '{{db-user}}',
                'db-password'                => '{{db-password}}',
                'use-secure'                 => '0',
                'use-secure-admin'           => '0',
                'use-rewrites'               => '0',
                'admin-lastname'             => 'Admin',
                'admin-firstname'            => 'Admin',
                'admin-email'                => 'admin@example.com',
                'admin-user'                 => 'admin',
                'admin-password'             => '123123q',
                'admin-use-security-key'     => '0',
                'backend-frontname'          => 'backend',
            ],
            'options_no_value' => [
                'cleanup-database',
            ],
        ],
    ],
    'scenario' => [
        'common_config' => [
            /* Common arguments passed to all scenarios */
            'arguments' => [
                'users'       => 100,
                'loops'       => 1,
                'ramp_period' => 120,
            ],
            /* Common settings for all scenarios */
            'settings' => [],
        ],
    ],
    'report_dir' => 'report',
];
