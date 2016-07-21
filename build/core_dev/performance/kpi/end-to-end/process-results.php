<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

$args = getopt('o:');
$required = ['o'];

if (count(array_intersect(array_keys($args), $required)) != count($required)) {
       echo <<<END
    Process WPT Results
    Required parameters:
         -o output_folder_for_wpt_results

    Example:
        php process_results.php -o magent2ce/dev/build/core_dev/performance/kpi/end-to-end/results

END;

    exit(1);
}

set_error_handler(function () {
        echo 'Error occurred!' . PHP_EOL;
        debug_print_backtrace();
        exit(255);
    }, E_ALL);

$outputFolder = $args['o'];

// Custom processing for issues with WPT reporting
selective_count($outputFolder, "customer-checkout-login", ["/login", "/customer/section/load", "/onepage"]);
selective_count($outputFolder, "customer-checkout-place-order", ["/payment-information", "/customer/section/load", "/success"]);
selective_count($outputFolder, "guest-checkout-place-order", ["/payment-information",  "/success"]);
remove_pages_not_found($outputFolder);

// Copy Checkout Start for both Guest and Customer tests
if (file_exists($outputFolder . "/checkout-start/result.json")) {
    if (!is_dir($outputFolder . "/customer-checkout-start")) {
        mkdir($outputFolder . "/customer-checkout-start");
    }
    copy($outputFolder . "/checkout-start/result.json", $outputFolder . "/customer-checkout-start/result.json");
}

function remove_pages_not_found($outputFolder){
    foreach(glob($outputFolder . "/*/result.json") as $resultFile) {
        copy($resultFile, $resultFile . '.' . time() . 'bak');

        // Read in file
        $json = json_decode(file_get_contents($resultFile),TRUE);

        $runCount=0;
        $loadMs=0;
        $fullyLoadedMs=0;
        $visualCompleteMs=0;
        foreach (range(1,10) as $runNumber) {
            if ($json["data"]["runs"][$runNumber]["firstView"] != null) {
                $runCount++;
                $pageNotFoundMs=0;
                foreach ($json["data"]["runs"][$runNumber]["firstView"]["requests"] as $request) {
                    if ($request["responseCode"] === "404") {
                        $pageNotFoundMs += $request["all_ms"];
                    }
                }
                $loadMs += $json["data"]["runs"][$runNumber]["firstView"]["loadTime"] - $pageNotFoundMs;
                $fullyLoadedMs += $json["data"]["runs"][$runNumber]["firstView"]["fullyLoaded"] - $pageNotFoundMs;
                $visualCompleteMs += $json["data"]["runs"][$runNumber]["firstView"]["visualComplete"] - $pageNotFoundMs;
            }
        }
        if (array_key_exists("firstView", $json["data"]["average"])) {
            $json["data"]["average"]["firstView"]["loadTime"] = $loadMs/$runCount;
            $json["data"]["average"]["firstView"]["fullyLoaded"] = $fullyLoadedMs/$runCount;
            $json["data"]["average"]["firstView"]["visualComplete"] = $visualCompleteMs/$runCount;
        }
        // Write out updated contents
        file_put_contents($resultFile, json_encode($json, TRUE));
    }
}

function selective_count($outputFolder, $scenario, $arrayPagesToCount) {
    $inputFile = $outputFolder . "/" . $scenario . "/result.json";
    if (file_exists($inputFile)) {
        // Backup file
        copy($inputFile, $inputFile . '.' . time() . 'bak');

        // Read in file
        $json = json_decode(file_get_contents($inputFile),TRUE);

        // Do processing work
        $sum_ms = 0;
        $runCount = 0;
        foreach (range(1,10) as $runNumber) {
            if ($json["data"]["runs"][$runNumber]["firstView"] != null) {
                $runCount++;
                foreach ($json["data"]["runs"][$runNumber]["firstView"]["requests"] as $request) {
                    foreach ($arrayPagesToCount as $pageToCount) {
                        if (strpos($request["url"], $pageToCount)) {
                            $sum_ms += $request["ttfb_ms"];
                        }
                    }
                }
            }
        }
        $calculatedTTFB = round($sum_ms/$runCount, 0);
        if (array_key_exists("firstView", $json["data"]["average"])) {
            $json["data"]["average"]["firstView"]["TTFB"] = $calculatedTTFB;
        }

        // Write out updated contents
        file_put_contents($inputFile, json_encode($json, TRUE));
    }
}