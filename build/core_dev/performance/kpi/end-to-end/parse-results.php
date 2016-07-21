<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

$args = getopt('l:r:o:e:');

if (count($args) < 4) {
    echo <<<END
    Parse WebPageTest results, print summary and write summaty csv file.
    Required parameters:
         -l comma-separated list of scenarios
         -e comma-separated list of scenarios with repeated view
         -r WebPageTest results directory
         -o output csv file

    Example:
        php parse-results.php -l home,cms -r results/ -o summary.csv -e home

END;

    exit(1);
}

$result = [
    [
        'Scenario',
        'First Byte Time',
        'Start Render',
        'Load Time',
        'Speed Index',
        'Visually Complete',
        'Assets Count',
        'Assets Size',
        'Diagram'
    ]
];

$scenarios = explode(',', $args['l']);
$scenariosRepeatedView = array_intersect($scenarios, explode(',', $args['e']));

$errors = [];

$resultFirstView = parseWebPageTestResults('first', $scenarios, 'firstView', $args['r'], $errors);
$resultRepeatedView = parseWebPageTestResults('repeated', $scenariosRepeatedView, 'repeatView', $args['r'], $errors);
$result = array_merge($result, $resultFirstView, $resultRepeatedView);
printResult($result);
writeCsvResult($result, $args['o']);

if (count($errors)) {
    echo "Errors:" . PHP_EOL;
    foreach ($errors as $error) {
        echo "    " . $error . PHP_EOL;
    }
    exit(255);
}

/**
 * @param string $suffix
 * @param array $scenarioList
 * @param string $viewCode
 * @param string $resultDirectory
 * @param int $runsCount
 * @param array $errors
 * @return array
 */
function parseWebPageTestResults($suffix, $scenarioList, $viewCode, $resultDirectory, &$errors)
{
    $result = [];
    foreach ($scenarioList as $scenarioName) {
        $resultXml = $resultDirectory . '/' . $scenarioName . '/result.xml';
        $xml = new SimpleXMLElement(file_get_contents($resultXml));
        $scenarioTitle = $scenarioName . ' (' . $suffix . ')';

        $bestRun = getBestRunResult($xml, $viewCode);

        if ($bestRun) {
            $result[] = [
                $scenarioTitle,
                (int)$bestRun->results->TTFB,
                (int)$bestRun->results->render,
                (int)$bestRun->results->loadTime,
                (int)$bestRun->results->SpeedIndex,
                (int)$bestRun->results->visualComplete,
                (int)$bestRun->results->requests,
                (int)$bestRun->results->bytesIn,
                (string)$bestRun->images->waterfall
            ];
        } else {
            $errors[] = 'Result for ' . $scenarioTitle . ' not found';
            $result[] = [$scenarioTitle, -1, -1, -1, -1,-1, -1, -1, ''];
        }
    }
    return $result;
}

/**
 * @param SimpleXMLElement $xml
 * @param string $subNode "firstView" or "repeatedView"
 * @return SimpleXMLElement
 */
function getBestRunResult(SimpleXMLElement $xml, $subNode)
{
    $bestRun = null;
    $bestLoadTime = 100000;
    foreach ($xml->xpath('//data/run') as $run) {
        if ($run->$subNode) {
            $loadTime = (float)$run->$subNode->results->loadTime;
            if ($loadTime < $bestLoadTime) {
                $bestLoadTime = $loadTime;
                $bestRun = $run->$subNode;
            }
        }
    }
    return $bestRun;
}

/**
 * @param array $result
 * @return void
 */
function printResult(array $result)
{
    $sizes = [];
    foreach ($result as $row) {
        foreach ($row as $column => $cell) {
            if (!isset($sizes[$column]) || $sizes[$column] < strlen($cell)) {
                $sizes[$column] = strlen($cell);
            }
        }
    }

    echo PHP_EOL . PHP_EOL . PHP_EOL;
    echo "====================================================================" . PHP_EOL . PHP_EOL;

    foreach ($result as $row) {
        foreach ($row as $column => $value) {
            printf('%-' . $sizes[$column] . 's  ', $value);
        }
        echo PHP_EOL;
    }
    echo "====================================================================" . PHP_EOL;
    echo PHP_EOL . PHP_EOL . PHP_EOL;
}

/**
 * @param array $result
 * @param string $filename
 * @return void
 */
function writeCsvResult(array $result, $filename)
{
    $file = fopen($filename, 'w');
    foreach ($result as $row) {
        fputcsv($file, $row, ';');
    }
    fclose($file);
}
