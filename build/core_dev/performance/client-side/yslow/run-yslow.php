<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

$args = getopt('s:o:h:');

if (!isset($args['s']) || !isset($args['o'])) {
    echo <<<END
    Run yslow for all scenarios in <scenario_directory> and writer results to <output-dir>/<scenario-name>.json
    Required parameters:
        -h <tested magento hostname>
        -s <scenarios_directory>
        -o <output_directory>

    Example:
        php run-yslow.php -h pt4-m.corp.magento.com -s scenarios/ -o results/


END;
    exit(1);
}

set_error_handler(function(){
    echo 'Error occurred!' . PHP_EOL;
    debug_print_backtrace();
    exit(255);
});

$scenarios = $args['s'];
$outputDir = $args['o'];
$host = $args['h'];

$exitCode = 0;
$scenariosArray = glob($scenarios . '/*.txt');
if (!count($scenariosArray)) {
    echo 'No scenarios found in ' . $scenarios . PHP_EOL;
    exit(1);
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

foreach ($scenariosArray as $scenario) {
    $scenarioOutput = $outputDir . '/' . basename($scenario, '.txt');
    echo "Running yslow with scenario {$scenario}..." . PHP_EOL;
    $url = str_replace('{host}', $host, trim(file_get_contents($scenario)));

    $output = '';
    $yslowExitCode = 0;
    exec('yslow -f json ' . escapeshellarg($url), $output, $yslowExitCode);
    exec('yslow -f plain ' . escapeshellarg($url) . ' > ' . $scenarioOutput . '.txt');
    if ($yslowExitCode > 0) {
        $exitCode = 1;
        echo 'Failed' . PHP_EOL;
    } else {
        echo 'Done' . PHP_EOL;
    }

    file_put_contents($scenarioOutput . '.json', $output);
}

exit($exitCode);
