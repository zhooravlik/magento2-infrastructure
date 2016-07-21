<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

$install_options_no_value[] = 'cleanup-database';

return [
    'install_options' => [
        'language'               => 'en_US',
        'timezone'               => 'America/Los_Angeles',
        'currency'               => 'USD',
        'db-model'               => '{{db-model}}',
        'db-host'                => '{{db-host}}',
        'db-name'                => '{{db-name}}',
        'db-user'                => '{{db-user}}',
        'db-password'            => '{{db-password}}',
        'admin-use-security-key' => '0',
        'use-rewrites'           => '1',
        'admin-lastname'         => 'Admin',
        'admin-firstname'        => 'Admin',
        'admin-email'            => 'admin@example.com',
        'admin-user'             => 'admin',
        'admin-password'         => '123123q', // must be at least of 7 both numeric and alphanumeric characters
        'base-url'               => '{{url}}',
        'session-save'           => 'db',
        'backend-frontname'      => 'backend',
    ],
    'install_options_no_value' => $install_options_no_value
];
