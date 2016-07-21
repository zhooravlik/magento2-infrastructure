<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once("report_utils.php");

$usageMessage =
'Usage:' . PHP_EOL
. '   php -f ' . str_replace(dirname(__FILE__), __FILE__, '')
. ' -- -m mainline_report.jtl -b branch_report.jtl -o output_file.xml ";"' . PHP_EOL
. PHP_EOL
. 'Parameters:' . PHP_EOL
. '   -m   - mainline report file' . PHP_EOL
. '   -b   - branch report file' . PHP_EOL
. '   -o   - output xml file' . PHP_EOL
. '   -p   - percent of measurements, that will be skipped (default = 15)' . PHP_EOL
. '   -t   - plain text report file (optional)' . PHP_EOL
. '   -d   - threshold for improvement/degradation for plain-text report (default = 1.5)' . PHP_EOL;

$args = getopt('m:b:o:p:t:d:');
if (empty($args)) {
    echo $usageMessage;
    exit(0);
}

$mainlineFile = $args['m'];
$branchFile = $args['b'];
$outputFile = $args['o'];
$plainReportFile = isset($args['t']) ? $args['t'] : false;
$skipMeasurementsPercent = isset($args['p']) && $args['p'] != '' ? min(100, max(0, $args['p'])) : 15;
$threshold = isset($args['d']) ? $args['d'] : 1.5;

try {
    $mainlineResults = readResponseTimeReport($mainlineFile);
    $branchResults = readResponseTimeReport($branchFile);

    $result = new SimpleXMLElement('<testResults version="1.2" />');
    $plainResult = [
        ['STEP', 'DIFFERENCE', '', 'RESULT']
    ];
    foreach (array_keys($mainlineResults) as $sampleName) {
        $success = isset($mainlineResults[$sampleName]['success'])
            && $mainlineResults[$sampleName]['success']
            && isset($branchResults[$sampleName])
            && isset($branchResults[$sampleName]['success'])
            && $branchResults[$sampleName]['success'];

        $deviation = $success
            ? getDeviation($mainlineResults[$sampleName]['times'], $branchResults[$sampleName]['times'])
            : 100;

        $sample = $result->addChild('httpSample');
        $sample->addAttribute('s', $success ? 'true' : 'false');
        $sample->addAttribute('t', round($deviation * 1000));
        $sample->addAttribute('lb', $sampleName . ' degradation');

        if (strpos($sampleName, 'SetUp - ') === false) {
            $plainResult[] = [
                $sampleName,
                $success ?
                    sprintf(
                        '%+.1f%%',
                        $deviation
                    ) :
                    '',
                $success ?
                    sprintf(
                        '(%+.0fms)',
                        -getImprovementInMilliseconds(
                            $mainlineResults[$sampleName]['times'],
                            $branchResults[$sampleName]['times']
                        )
                    ) :
                    '',
                $success ?
                    ($deviation < -$threshold ? 'improvement' : ($deviation > $threshold ? 'DEGRADATION' : 'ok')) :
                    'FAILED'
            ];
        }
    }

    $dom = new DOMDocument("1.0");
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($result->asXML());
    file_put_contents($outputFile, $dom->saveXML());

    printPlainReport($plainResult, $plainReportFile);
} catch (\Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
