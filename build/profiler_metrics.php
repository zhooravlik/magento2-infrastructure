<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
?>
<?php return [
    'test execution time (ms)' =>            ['integration_test'],
    /* Application framework metrics */
    'bootstrap time (ms)' =>                 ['bootstrap'],
    'modules initialization time (ms)' =>    ['init_modules'],
    'request initialization time (ms)' =>    ['init_request'],
    'routing time (ms)' =>                   [
        'routing_init', 'db_url_rewrite', 'config_url_rewrite', 'routing_match_router',
    ],
    'pre dispatching time (ms)' =>           ['predispatch'],
    'layout overhead time (ms)' =>           ['layout_load', 'layout_generate_xml', 'layout_generate_blocks'],
    'response rendering time (ms)' =>        ['layout_render'],
    'post dispatching time (ms)' =>          ['postdispatch', 'response_send'],
    /* Magento_Catalog module metrics */
    'product save time (ms)' =>              ['catalog_product_save'],
    'product load time (ms)' =>              ['catalog_product_load'],
    'category save time (ms)' =>             ['catalog_category_save'],
    'category load time (ms)' =>             ['catalog_category_load'],
];
