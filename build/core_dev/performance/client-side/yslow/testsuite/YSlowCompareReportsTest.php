<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
class YSlowCompareReportsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Parses YSlow reports. Returns data of interest
     *
     * @return array ['Scenario Rule' => [Score in mainline, Score in teamfork, YSlow message]]
     */
    public function resultsDataProvider()
    {
        $mainline = $this->readYslowReport(YSLOW_TESTS_RESULTS_MAINLINE, 'json', '/^([^.]+)\./');
        $teamfork = $this->readYslowReport(YSLOW_TESTS_RESULTS_TEAMFORK, 'json', '/^([^.]+)\./');
        foreach ($mainline as $scenarioName => $result) {
            $ruleNames = array_keys($result["g"]);
            foreach ($ruleNames as $ruleName) {
                //Skip the rule that doesn't have numerical grade
                if (!array_key_exists("score", $result["g"][$ruleName])) {
                    break;
                }
                $data[$scenarioName . ' ' . $ruleName] = [
                    'score_mainline' => $result["g"][$ruleName]["score"],
                    'score_teamfork' => null,
                    'message' => $result["g"][$ruleName]["message"]
                ];
            }
        }
        foreach ($teamfork as $scenarioName => $result) {
            $ruleNames = array_keys($result["g"]);
            foreach ($ruleNames as $ruleName) {
                if (array_key_exists($scenarioName . ' ' . $ruleName, $data)) {
                    $data[$scenarioName . ' ' . $ruleName]['score_teamfork'] = $result["g"][$ruleName]["score"];
                }
            }
        }
        return $data;
    }

    /**
     * Verifies if YSlow performance score on team fork is greater or equal to mainline score.
     *
     * @param integer $scoreMainline Score calculated by YSlow on mainline
     * @param integer $scoreTeamfork Score calculated by YSlow on team fork
     * @param string $ruleDescription Explanation for the rule provided by YSlow
     *
     * @dataProvider resultsDataProvider
     */
    public function testDegradation($scoreMainline, $scoreTeamfork, $ruleDescription)
    {
        $this->assertGreaterThanOrEqual(
            $scoreMainline,
            $scoreTeamfork,
            $ruleDescription . "\n" . ' Details: http://yslow.org/faq/#faq_grading');
    }

    /**
     * Parses YSlow reports into an array of data
     *
     * @param string $directory Directory to read from
     * @param string $format Format of reports to be read
     * @param string $scenarioNamePattern Pattern to extract the scenario name from the file name
     *
     * @return array YSlow reports, each one is parsed into an array of data
     */
    private function readYslowReport($directory, $format, $scenarioNamePattern)
    {
        //Read file names
        $filenameList = scandir($directory);
        $filenameList = array_filter(
            $filenameList,
            function ($v) use ($format) {
                return (strpos($v, $format));
            }
        );
        //Read performance results
        foreach ($filenameList as $filename) {
            preg_match($scenarioNamePattern, $filename, $scenarioName);
            $result[$scenarioName[1]] = json_decode(
                file_get_contents($directory . DIRECTORY_SEPARATOR . $filename),
                true
            );
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(
                    $directory . DIRECTORY_SEPARATOR . $filename . ' is not a valid ' . $format . ' file'
                );
            }
        }
        return $result;
    }
}