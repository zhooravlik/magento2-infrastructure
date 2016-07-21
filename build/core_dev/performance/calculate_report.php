<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once("report_utils.php");

$usageMessage =
    'Usage:' . PHP_EOL
    . '   php -f ' . str_replace(dirname(__FILE__), __FILE__, '')
    . ' -- -m mainline_report.jtl ";"' . PHP_EOL
    . PHP_EOL
    . 'Parameters:' . PHP_EOL
    . '   -m   - mainline report file' . PHP_EOL
    . '   -o   - output xml file' . PHP_EOL
    . '   -p   - percent of measurements, that will be skipped (default = 15)' . PHP_EOL
    . '   -t   - plain text report file (optional)' . PHP_EOL
    . '   -d   - threshold for improvement/degradation for plain-text report (default = 1.5)' . PHP_EOL
    . '   -e   - extra json formatted data (i.e. "build_id": 123)' . PHP_EOL
    . '   -x   - maximum thresholds formatted as json data'  . PHP_EOL
    . '   -n   - minimum thresholds formatted as json data' . PHP_EOL
    . '   -j   - json output file' . PHP_EOL
    . '   -a   - absolute json output file' . PHP_EOL;

$args = getopt('m:o:p:t:d:e:x:n:j:a:');
if (empty($args)) {
    echo $usageMessage;
    exit(0);
}

$mainlineFile = $args['m'];
$extraData = json_decode($args['e'],TRUE);
$outputFile = $args['o'];
$plainReportFile = isset($args['t']) ? $args['t'] : false;
$skipMeasurementsPercent = isset($args['p']) && $args['p'] != '' ? min(100, max(0, $args['p'])) : 15;
$threshold = isset($args['d']) ? $args['d'] : 1.5;
$maximums = json_decode(file_get_contents($args['x']), TRUE)['_source'];
$minimums = json_decode(file_get_contents($args['n']), TRUE)['_source'];
$jsonOutputFile = $args['j'];
$absoluteJsonOutputFile = $args['a'];


try {
    output_json($mainlineFile, $extraData, $jsonOutputFile);
    output_reports($mainlineFile, $maximums, $threshold, $outputFile, $plainReportFile);
    output_absolute_json($mainlineFile, $maximums, $minimums, $absoluteJsonOutputFile);

} catch (\Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

function output_absolute_json($mainlineFile, $maximums, $minimums, $absoluteJsonOutputFile) {
    $mainlineResults = readResponseTimeReport($mainlineFile);
    foreach (array_keys($maximums) as $metric) {
        if ($metric != 'build_id' && $metric != 'commit' && $metric != 'branch') {
            if (array_key_exists($metric, $mainlineResults) && array_key_exists($metric, $minimums)) {
                $mainlineMetricValue = getMeanValue($mainlineResults[$metric]['times']);
                $maximums[$metric] =
                    ($maximums[$metric] > $mainlineMetricValue && $mainlineMetricValue > $minimums[$metric])
                        ? $mainlineMetricValue : $maximums[$metric];
            }
        }
    }

    # Output new absolute maximums to be posted to ES.
    file_put_contents($absoluteJsonOutputFile, json_encode($maximums, TRUE));
}

function output_reports($mainlineFile, $maximums, $threshold, $outputFile, $plainReportFile){
    # Calculate deviations from absolute maximums
    $mainlineResults = readResponseTimeReport($mainlineFile);
    $result = new SimpleXMLElement('<testResults version="1.2" />');
    $plainResult = [
        ['STEP', 'DIFFERENCE', '', 'RESULT']
    ];

    foreach(array_keys($maximums) as $sampleName) {
        $success = isset($mainlineResults[$sampleName]['success'])
            && $mainlineResults[$sampleName]['success'];

        $deviation = $success
                ? getDeviation(array_pad(array(), sizeof($mainlineResults[$sampleName]['times']), $maximums[$sampleName]), $mainlineResults[$sampleName]['times'])
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
                            array_pad(array(), sizeof($mainlineResults[$sampleName]['times']), $maximums[$sampleName]),
                            $mainlineResults[$sampleName]['times']
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

}
function output_json($mainlineFile, $extraData, $jsonOutputFile) {
        # Generate JSON data for persistence
        $mainlineResults = readResponseTimeReport($mainlineFile);
        $result = [];
        foreach (array_keys($mainlineResults) as $sampleName) {
            if (strpos($sampleName, 'SetUp - ') === false) {
                $result["$sampleName"] = getMeanValue($mainlineResults[$sampleName]['times']);
            }
        }
        file_put_contents($jsonOutputFile, json_encode(array_merge($extraData,$result), TRUE));
}