#!/usr/bin/env php
<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require __DIR__ . '/../../../../../tools/bootstrap_tools.php';

$app = new Magento\Tools\SemanticVersionChecker\Console\Application();
$app->run();