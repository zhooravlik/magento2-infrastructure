<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

$args = getopt('w:s:o:b:h:t:l:c:a:');
$required = ['w', 's', 'o', 'b', 'h', 'l', 'c'];

if (count(array_intersect(array_keys($args), $required)) != count($required)) {
    echo <<<END
    Run WebPageTest for all scenarios in <scenarios_directory> and writes results to <output-dir>/scenario-name.json and <output-dir>/scenario-name/[report]
    Required parameters:
         -w webpagetest_server
         -h magento hostname
         -s scenarios_directory
         -b location:browser
         -o output-dir
         -t seconds to wait for WebPageTest results (optional, default = 300)
         -l list of scenarios to run
         -c list of scenarios to measure with "repeated view"
         -a Additional command-line params for webpagetest-cli (optional)

    Example:
        php run-webpagetest.php -w pt4-web.corp.magento.com -h pt4-m.corp.magento.com -b Win7-m2:chrome -s scenarios/ -o results/ -l homepage,cms -c cms


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
$outputDir = $args['o'];
$browser = $args['b'];
$host = $args['h'];
$runs = 10;
$timeout = isset($args['t']) ? $args['t'] : 300;
$scenariosList = isset($args['l']) && trim($args['l']) != '' ? explode(',', $args['l']) : [];
$repeatedViewList = isset($args['c']) && trim($args['c']) != '' ? explode(',', $args['c']) : [];
$additionalWptParams = isset($args['a']) ? $args['a'] : '';

$exitCode = 0;
$scenariosArray = [];
foreach (glob($scenarios . '/*.txt') as $scenarioFile) {
    if (in_array(basename($scenarioFile, '.txt'), $scenariosList)) {
        $scenariosArray[] = $scenarioFile;
    }
}
if (!count($scenariosArray)) {
    echo 'No scenarios found in ' . $scenarios . PHP_EOL;
    exit(1);
}

foreach ($scenariosArray as $scenario) {
    $scenarioOutput = $outputDir . '/' . basename($scenario, '.txt');
    if (!is_dir($scenarioOutput)) {
        mkdir($scenarioOutput, 0777, true);
    }

    $compiledScenario = $scenario . '.compiled';

    file_put_contents($compiledScenario, str_replace('{host}', $host, file_get_contents($scenario)));

    try {
        echo "Running webpagetest with scenario {$scenario}..." . PHP_EOL;

        $wptResponse = runWebPageTest(
            $compiledScenario,
            $server,
            $runs,
            $browser,
            in_array(basename($scenario, '.txt'), $repeatedViewList),
            $additionalWptParams
        );

        echo 'Waiting...' . PHP_EOL;
        $responseCode = waitForWebPageTest($wptResponse, $timeout);

        echo 'Writing results to ' . $scenarioOutput . '...' . PHP_EOL;
        downloadResults($wptResponse, $scenarioOutput);

        if ($responseCode != 200) {
            echo "An error occurred while testing {$scenario} on {$server}" . PHP_EOL;
            echo "WebPageTest response code: {$responseCode}";
            $exitCode = 1;
            continue;
        }
        echo 'Done' . PHP_EOL;
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
 * @param bool $repeatedView
 * @param string $additionalWptParams
 * @return stdClass WPTResponse
 * @throws Exception
 */
function runWebPageTest($scenario, $server, $runs, $browser, $repeatedView, $additionalWptParams)
{
    $commandLine = "webpagetest test {$scenario} -v -s {$server} -r {$runs} -l {$browser} {$additionalWptParams}";
    if (!$repeatedView) {
        $commandLine .= ' -f';
    }

    echo 'Running `' . $commandLine . '`...' . PHP_EOL;

    $output = `$commandLine`;
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
 * @return void
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
