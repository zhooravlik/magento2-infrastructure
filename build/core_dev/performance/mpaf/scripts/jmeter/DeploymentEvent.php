<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Triggers a deployment event.
 */


$url = "http://" . $_POST['stats_server'] . "/RPUSH/logstash";

$environment = $_POST['environment'];
$startTime = $_POST['startTime'];
$endTime = $_POST['endTime'];
$data='{"environment":"'. $environment .'","eventType":"jmeter-event","deleted":false,"startTime":"' . $startTime . '","endTime":"' . $endTime . '"}';

$ch = curl_init();
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLINFO_CONTENT_TYPE, "application/json");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$results = curl_exec($ch);
$errors = curl_error($ch);
$info = curl_getinfo($ch);
curl_close ($ch);

if ($errors) {
    printf ("Errors: %s\n",$errors);
}
?>
