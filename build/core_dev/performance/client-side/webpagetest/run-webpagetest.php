<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

$args = getopt('w:s:r:o:b:h:t:l:');
$required = ['w', 's', 'r', 'o', 'b', 'h'];

if (count(array_intersect(array_keys($args), $required)) != count($required)) {
    echo <<<END
    Run WebPageTest for all scenarios in <scenarios_directory> and writes results to <output-dir>/scenario-name.json and <output-dir>/scenario-name/[report]
    Required parameters:
         -w webpagetest_server
         -h magento hostname
         -s scenarios_directory
         -b location:browser
         -r runs
         -o output-dir
         -t seconds to wait for WebPageTest results (optional, default = 300)
         -l list of scenarios to run (optional, default = all)

    Example:
        php run-webpagetest.php -w pt4-web.corp.magento.com -h pt4-m.corp.magento.com -b Win7-m2:chrome -s scenarios/ -r 10 -o results/


END;

    exit(1);
}


set_error_handler(function () {
    echo 'Error occurred!' . PHP_EOL;
    debug_print_backtrace();
    exit(255);
}, E_ALL);

$server = $args['w'];
$scenarios = $args['s'];
$runs = max(1, $args['r']);
$outputDir = $args['o'];
$browser = $args['b'];
$host = $args['h'];
$timeout = isset($args['t']) ? $args['t'] : 300;
$scenariosList = isset($args['l']) && trim($args['l']) != '' ? explode(',', $args['l']) : [];

$exitCode = 0;
if (!empty($scenariosList)) {
    $scenariosArray = [];
    foreach (glob($scenarios . '/*.txt') as $scenarioFile) {
        if (in_array(basename($scenarioFile, '.txt'), $scenariosList)) {
            $scenariosArray[] = $scenarioFile;
        }
    }
} else {
    $scenariosArray = glob($scenarios . '/*.txt');
}
if (!count($scenariosArray)) {
    echo 'No scenarios found in ' . $scenarios . PHP_EOL;
    exit(1);
}

foreach ($scenariosArray as $scenario) {
    try {
        $scenarioOutput = $outputDir . '/' . basename($scenario, '.txt');
        if (!is_dir($scenarioOutput)) {
            mkdir($scenarioOutput, 0777, true);
        }

        $compiledScenario = $scenario . '.compiled';

        file_put_contents($compiledScenario, str_replace('{host}', $host, file_get_contents($scenario)));
        $runsRemaining = $runs;
        $iteration = 0;
        while ($runsRemaining > 0) {
            $iteration++;

            echo "Running webpagetest with scenario {$scenario} (iteration {$iteration})..." . PHP_EOL;

            $iterationOutput = $scenarioOutput . '/' . $iteration;

            $runsRemaining -= ($toDo = min(10, $runsRemaining));
            $wptResponse = runWebPageTest($compiledScenario, $server, $toDo, $browser);

            echo 'Waiting...' . PHP_EOL;
            $responseCode = waitForWebPageTest($wptResponse, $timeout);

            echo 'Writing results to ' . $iterationOutput . '...' . PHP_EOL;
            downloadResults($wptResponse, $iterationOutput);

            echo 'Parsing results...';
            parseWebPageTestResults($outputDir, basename($scenario, '.txt'));

            if ($responseCode != 200) {
                echo "An error occurred while testing {$scenario} on {$server}" . PHP_EOL;
                echo "WebPageTest response code: {$responseCode}";
                $exitCode = 1;
                continue;
            }
            echo 'Done' . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo $e->getMessage();
        echo PHP_EOL;
        $exitCode = 1;
        break;
    } finally {
        unlink($compiledScenario);
    }
}

exit($exitCode);


/**
 * @param string $scenario
 * @param string $server
 * @param int $runs
 * @param string $browser
 * @return stdClass WPTResponse
 * @throws Exception
 */
function runWebPageTest($scenario, $server, $runs, $browser)
{
    $output = `webpagetest test {$scenario} -s {$server} -r {$runs} -f -l {$browser}`;
    $wptResponse = json_decode($output);
    if ($wptResponse === null) {
        throw new \Exception('WebPageTest cli output cannot be parsed as json:' . PHP_EOL . PHP_EOL . $output);
    }
    if ($wptResponse->statusCode != 200) {
        throw new \Exception('WebPageTest returned error:' . PHP_EOL . PHP_EOL . $output);
    }
    return $wptResponse;
}

/**
 * @param stdClass $wptResponse
 * @param int $timeout
 * @return int HTTP response code
 * @throws Exception
 */
function waitForWebPageTest($wptResponse, $timeout)
{
    $responseCode = 0;
    $loop = $timeout * 4; // one loop = 0.25 seconds
    do {
        if ($loop-- <= 0) {
            throw new \Exception('Timeout exceeded');
        }
        $ch = curl_init($wptResponse->data->xmlUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        $xml = new SimpleXMLElement($result);
        $responseCode = (int)$xml->statusCode;

        if (!$responseCode) {
            throw new \Exception('WebPageTest returned invalid XML:' . PHP_EOL . PHP_EOL . $result);
        }
        usleep(250000);
    } while ($responseCode < 200 || $xml->data->runs == '');
    return $responseCode;
}

/**
 * @param stdClass $wptResponse
 * @param string $directory
 */
function downloadResults($wptResponse, $directory)
{
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($directory . '/testId.txt', $wptResponse->data->testId);
    file_put_contents($directory . '/ownerKey.txt', $wptResponse->data->ownerKey);
    file_put_contents($directory . '/result.json', file_get_contents($wptResponse->data->jsonUrl));
    file_put_contents($directory . '/result.xml', file_get_contents($wptResponse->data->xmlUrl));
    file_put_contents($directory . '/result.html', file_get_contents($wptResponse->data->userUrl));
    file_put_contents($directory . '/summary.csv', file_get_contents($wptResponse->data->summaryCSV));
    file_put_contents($directory . '/detail.csv', file_get_contents($wptResponse->data->detailCSV));

    echo 'Downloading images...';
    $xml = new SimpleXMLElement(file_get_contents($directory . '/result.xml'));
    foreach ($xml->xpath('//data/run/firstView/images/*') as $url) {
        $url = (string)$url;
        file_put_contents($directory . '/' . basename($url), file_get_contents($url));
    }
}

/**
 * @param string $outputDir
 * @param string $scenarioName
 */
function parseWebPageTestResults($outputDir, $scenarioName)
{
    $result = new stdClass();
    $result->grades = new stdClass();

    $resultHtml = file_get_contents($outputDir . '/' . $scenarioName . '/1/result.html');
    $match = [];
    preg_match_all('|#first_byte_time"><h2 class="(\w+)"|', $resultHtml, $match);
    $result->grades->firstByteTime = (string)$match[1][0];
    preg_match_all('|#keep_alive_enabled"><h2 class="(\w+)"|', $resultHtml, $match);
    $result->grades->keepAliveEnabled = (string)$match[1][0];
    preg_match_all('|#compress_text"><h2 class="(\w+)"|', $resultHtml, $match);
    $result->grades->compressTransfer = (string)$match[1][0];
    preg_match_all('|#compress_images"><h2 class="(\w+)"|', $resultHtml, $match);
    $result->grades->compressImages = (string)$match[1][0];
    preg_match_all('|#cache_static_content"><h2 class="(\w+)"|', $resultHtml, $match);
    $result->grades->cacheStaticContent = (string)$match[1][0];

    $result->runs = [];

    foreach (glob($outputDir . '/' . $scenarioName . '/*/result.xml') as $resultXmlFilename) {
        $detailCsvFilename = dirname($resultXmlFilename) . '/detail.csv';
        $resultXml = new SimpleXMLElement(file_get_contents($resultXmlFilename));

        $detailCsv = [];
        $csvHandle = fopen($detailCsvFilename, 'r');
        $header = fgetcsv($csvHandle, null, ',');
        while (is_array($row = fgetcsv($csvHandle, null, ','))) {
            array_pop($row);
            $detailCsv[] = array_combine($header, $row);
        }

        /** @var SimpleXMLElement $run */
        foreach ($resultXml->data->run as $run) {
            $runResult = new stdClass();
            $runResult->documentComplete = (int)$run->firstView->results->docTime;
            $runResult->startRender = (int)$run->firstView->results->render;
            $runResult->fullyLoaded = (int)$run->firstView->results->fullyLoaded;
            $runResult->firstByteTime = (int)$run->firstView->results->TTFB;
            $runResult->requests = (int)$run->firstView->results->requests;
            $runResult->bytesIn = (int)$run->firstView->results->bytesIn;

            $js = [];
            $css = [];
            $fonts = [];
            $images = [];
            foreach ($detailCsv as $item) {
                if ($item['Run'] == (string)$run->id) {
                    if (stripos($item['Content Type'], 'javascript') !== false) {
                        $js[] = $item['Object Size'];
                    } elseif (stripos($item['Content Type'], 'text/css') === 0) {
                        $css[] = $item['Object Size'];
                    } elseif (stripos($item['Content Type'], 'image') === 0) {
                        $images[] = $item['Object Size'];
                    } elseif (stripos($item['Content Type'], 'application/font') === 0) {
                        $fonts[] = $item['Object Size'];
                    }
                }
            }

            $runResult->jsCount = count($js);
            $runResult->jsSize = array_sum($js);
            $runResult->cssCount = count($css);
            $runResult->cssSize = array_sum($css);
            $runResult->fontsCount = count($fonts);
            $runResult->fontsSize = array_sum($fonts);
            $runResult->imagesCount = count($images);
            $runResult->imageSize = array_sum($images);

            $result->runs[] = $runResult;
        }
    }

    file_put_contents($outputDir . '/' . $scenarioName . '.json', json_encode($result, JSON_PRETTY_PRINT));
}