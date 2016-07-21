<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

function readResponseTimeReport($filename)
{
    $result = [];
    $f = fopen($filename, 'r');
    while (!feof($f) && is_array($line = fgetcsv($f))) {
        $responseTime = $line[1];
        $title = $line[2];
        $success = $line[7];
        if (!isset($result[$title])) {
            $result[$title] = ['times' => [], 'success' => true];
        }

        $result[$title]['times'][] = $responseTime;
        $result[$title]['success'] &= ($success == 'true');
    }
    return $result;
}

function getMeanValue(array $times)
{
    global $skipMeasurementsPercent;
    sort($times);
    $slice = array_slice($times, 0, round(count($times) - count($times) * $skipMeasurementsPercent / 100));

    return array_sum($slice) / count($slice);
}

function getDeviation(array $mainlineResults, array $branchResults)
{
    return 100 * (getMeanValue($branchResults) / getMeanValue($mainlineResults) - 1);
}

function getImprovementInMilliseconds(array $mainlineResults, array $branchResults)
{
    return getMeanValue($mainlineResults) - getMeanValue($branchResults);
}

function printPlainReport(array $plainReport, $plainReportFile)
{
    $result = '';
    foreach ($plainReport as $sample) {
        $result .= sprintf('%-32s %10s %-10s %s' . PHP_EOL, $sample[0], $sample[1], $sample[2], $sample[3]);
    }
    echo PHP_EOL . PHP_EOL . PHP_EOL;
    echo "====================================================================" . PHP_EOL . PHP_EOL;
    echo $result . PHP_EOL;
    echo "====================================================================" . PHP_EOL;
    echo PHP_EOL . PHP_EOL . PHP_EOL;
    if ($plainReportFile !== false) {
        file_put_contents($plainReportFile, $result);
    }
}