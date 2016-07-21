<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

return [
    'db-host'                => '{{db-host}}',
    'db-user'                => '{{db-user}}',
    'db-password'            => '{{db-password}}',
    'db-name'                => '{{db-name}}',
    'db-prefix'              => '{{db_table_prefix}}',
    'backend-frontname'      => 'backend',
    'base-url'               => '{{url}}/',
    'session-save'           => 'db',
    'admin-user'             => \Magento\TestFramework\Bootstrap::ADMIN_NAME,
    'admin-password'         => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
    'admin-email'            => \Magento\TestFramework\Bootstrap::ADMIN_EMAIL,
    'admin-firstname'        => \Magento\TestFramework\Bootstrap::ADMIN_FIRSTNAME,
    'admin-lastname'         => \Magento\TestFramework\Bootstrap::ADMIN_LASTNAME,
    'admin-use-security-key' => '0',
    'use-rewrites'           => '0',
    'cleanup-database'       => true,
];
