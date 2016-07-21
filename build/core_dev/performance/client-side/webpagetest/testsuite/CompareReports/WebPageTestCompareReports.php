<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace CompareReports;

class WebPageTestCompareReports extends \PHPUnit_Framework_TestCase
{
    /**
     * Allowed time deviation
     *
     * @var float
     */
    protected $timeDeviation = 5;

    /**
     * Allowed size deviation
     *
     * @var float
     */
    protected $sizeDeviation = 3;

    /**
     * Allowed count deviation
     *
     * @var float
     */
    protected $countDeviation = 0;

    /**
     * Output file with page fully loaded time for each scenario in text format
     *
     * @var string
     */
    protected $pageFullTimeDiffFile = 'time-difference.txt';

    /**
     * SetUP incoming data
     *
     * @return void
     */
    protected function setUp()
    {
        if (isset($_SERVER['timeDeviation'])) {
            $this->timeDeviation = $_SERVER['timeDeviation'];
        }
        if (isset($_SERVER['sizeDeviation'])) {
            $this->sizeDeviation = $_SERVER['sizeDeviation'];
        }
        if (isset($_SERVER['countDeviation'])) {
            $this->countDeviation = $_SERVER['countDeviation'];
        }
        if (isset($_SERVER['outputFile'])) {
            $this->pageFullTimeDiffFile = $_SERVER['outputFile'];
        }
    }

    /**
     * Parses WPT reports and returns data array
     *
     * @return string[]
     */
    public function incomingDataProvider()
    {
        $wptReportsDirectory = __DIR__
            . DIRECTORY_SEPARATOR
            . '..'
            . DIRECTORY_SEPARATOR
            . '..'
            . DIRECTORY_SEPARATOR
            . 'results';

        $mainline = $this->readReport(
            '/.*\.json$/',
            '/([^.]+)\./',
            $wptReportsDirectory . DIRECTORY_SEPARATOR . 'mainline'
        );
        $teamfork = $this->readReport(
            '/.*\.json$/',
            '/([^.]+)\./',
            $wptReportsDirectory . DIRECTORY_SEPARATOR . 'team-fork'
        );

        $result = [];
        foreach ($mainline as $key => $value) {
            $result[$key]['scenario'] = $key;
            $result[$key]['mainline'] = $value;
        }
        foreach ($teamfork as $key => $value) {
            $result[$key]['teamfork'] = $value;
        }

        return $result;
    }

    /**
     * Validate that the performance data in the team fork and mainline branch are correct
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testValidateWebPageTestData($scenarioName, $mainlineValues, $teamforkValues)
    {
        $this->assertTrue(
            (count($mainlineValues['runs']) > 0),
            "Mainline results in the last step of `{$scenarioName}` scenario are incorrect."
        );

        $this->assertTrue(
            (count($teamforkValues['runs']) > 0),
            "Team-fork results in the last step of `{$scenarioName}` scenario are incorrect."
        );
    }

    /**
     * Verifies that the time to full page load in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestTimePerformance($scenarioName, $mainlineValues, $teamforkValues)
    {
        $mainlineMeanValue = $this->getMeanValue($mainlineValues['runs'], 'fullyLoaded', 'firstByteTime');
        $teamforkMeanValue = $this->getMeanValue($teamforkValues['runs'], 'fullyLoaded', 'firstByteTime');
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'fullyLoaded',
            'firstByteTime'
        );

        file_put_contents(
            $this->pageFullTimeDiffFile,
            sprintf(
                '%-25s %5.0fms %5.0fms %+6.1f%% (%+.0fms)',
                $scenarioName,
                $mainlineMeanValue,
                $teamforkMeanValue,
                100 * ($teamforkMeanValue / $mainlineMeanValue - 1),
                $teamforkMeanValue - $mainlineMeanValue
            ) . PHP_EOL,
            FILE_APPEND
        );

        $this->assertTrue(
            $this->timeDeviation >= $result,
            "Time to full page load in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the number of requests in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestRequestsCount($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'requests'
        );
        $this->assertTrue(
            $this->countDeviation >= $result,
            "Number of requests in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the size of JavaScript files in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestJsSize($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'jsSize'
        );
        $this->assertTrue(
            $this->sizeDeviation >= $result,
            "Size of JavaScript files in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the count of JavaScript files in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestJsCount($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'jsCount'
        );
        $this->assertTrue(
            $this->countDeviation >= $result,
            "Count of JavaScript files in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the size of CSS files in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestCssSize($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'cssSize'
        );
        $this->assertTrue(
            $this->sizeDeviation >= $result,
            "Size of CSS files in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the count of CSS files in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestCssCount($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'cssCount'
        );
        $this->assertTrue(
            $this->countDeviation >= $result,
            "Count of CSS files in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the size of images in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestImagesSize($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'imageSize'
        );
        $this->assertTrue(
            $this->sizeDeviation >= $result,
            "Size of images in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the count of images in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestImagesCount($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'imagesCount'
        );
        $this->assertTrue(
            $this->countDeviation >= $result,
            "Size of images in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the size of fonts in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestFontsSize($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'fontsSize'
        );
        $this->assertTrue(
            $this->sizeDeviation >= $result,
            "Size of fonts files in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the count of fonts in the last step of scenario is less or equal than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestFontsCount($scenarioName, $mainlineValues, $teamforkValues)
    {
        $result = $this->getDeviation(
            $mainlineValues['runs'],
            $teamforkValues['runs'],
            'fontsCount'
        );
        $this->assertTrue(
            $this->countDeviation >= $result,
            "Count of fonts files in the last step of `{$scenarioName}` scenario is greater than on mainline. "
            . "Team-fork has {$result}% degradation."
        );
    }

    /**
     * Verifies that the page grade in the last step of scenario is not worse than on mainline
     *
     * @param string $scenarioName
     * @param [] $mainlineValues
     * @param [] $teamforkValues
     *
     * @dataProvider incomingDataProvider
     *
     * @return void
     */
    public function testWebPageTestGrades($scenarioName, $mainlineValues, $teamforkValues)
    {
        foreach ($mainlineValues['grades'] as $key => $grade) {
            $this->assertTrue(
                $this->convertGradesToNumbers($grade) >= $this->convertGradesToNumbers($teamforkValues['grades'][$key]),
                "Your `{$key}` grade in the last step of `{$scenarioName}` scenario is worse than that was. "
                . "The Team-fork value is `{$teamforkValues['grades'][$key]}`, the Mainline value is `{$grade}`"
            );
        }
    }

    /**
     * Parses WPT reports into an array of data
     *
     * @param string $filenamePattern Pattern to sort out files from the results folder
     * @param string $sNamePattern Pattern to extract the scenario name from the file name
     * @param string $directory Path to directory with reports
     *
     * @return string[]
     */
    private function readReport($filenamePattern, $sNamePattern, $directory)
    {
        $result = [];
        //Read file names
        $filenameList = scandir($directory);
        $filenameList = array_filter(
            $filenameList,
            function ($v) use ($filenamePattern) {
                return preg_match($filenamePattern, $v);
            }
        );
        //Read performance results
        foreach ($filenameList as $filename) {
            preg_match($sNamePattern, $filename, $sName);
            $result[$sName[1]] = json_decode(
                file_get_contents($directory . DIRECTORY_SEPARATOR . $filename),
                true
            );
        }
        return $result;
    }

    /**
     * Conver grades to numbers
     *
     * @param string $grade Grade
     *
     * @return int
     */
    protected function convertGradesToNumbers($grade)
    {
        $grade = strtoupper($grade);
        $result = strtr($grade, "ABCDEF", "123456");
        if (!is_numeric($result)) {
            $result = 7;
        }
        return $result;
    }

    /**
     * Get mean values
     *
     * @param [] $values Array with scenario results
     * @param string $sName Scenario name
     * @param string $subtractionName Scenario name for subtraction
     *
     * @return float
     */
    protected function getMeanValue($values, $sName, $subtractionName = '')
    {
        $toSort = [];
        if ($subtractionName != '') {
            foreach ($values as $value) {
                $toSort[] = $value[$sName] - $value[$subtractionName];
            }
        } else {
            foreach ($values as $value) {
                $toSort[] = $value[$sName];
            }
        }
        sort($toSort);
        $count = count($toSort);
        $midLevel = floor(($count - 1 ) / 2);
        if ($count % 2) {
            $median = $toSort[$midLevel];
        } else {
            $low = $toSort[$midLevel];
            $high = $toSort[$midLevel + 1];
            $median = (($low + $high) / 2);
        }
        return $median;
    }

    /**
     * Get deviation
     *
     * @param [] $mResults Mainline results
     * @param [] $tResults Team-fork results
     * @param string $sName Scenario name
     * @param string $subtractionName Scenario name for subtraction
     *
     * @return float
     */
    protected function getDeviation($mResults, $tResults, $sName, $subtractionName = '')
    {
        $tMeanValue = $this->getMeanValue($tResults, $sName, $subtractionName);
        $mMeanValue = $this->getMeanValue($mResults, $sName, $subtractionName);
        if ($mMeanValue == 0 && $tMeanValue == 0) {
            return 0;
        } elseif ($mMeanValue == 0 && $tMeanValue > 0) {
            return 100;
        } else {
            $result = 100 * ($tMeanValue / $mMeanValue - 1);
            return round($result, 1);
        }
    }
}
