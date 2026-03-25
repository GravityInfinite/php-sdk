<?php

namespace GESDKDemo;
require "GEPhpSdk.php";


use Exception;
use GEData\GEAnalytics;
use GEData\GEBatchConsumer;
use GEData\GEDataException;
use GEData\GEDebugConsumer;
use GEData\GELog;


// Replace with your actual server URL. It's recommended to use environment variables:
// $serverUrl = getenv('GE_SERVER_URL');
const SERVER_URL = 'https://backend.gravity-engine.com/event_center/api/v1/event/collect/?access_token=__XXX__';

/**
 * report data by http
 * @return GEAnalytics|null
 */
function get_batch_sdk()
{
    GELog::$enable = true;
    $batchConsumer = new GEBatchConsumer(SERVER_URL,);
    return new GEAnalytics($batchConsumer);
}

/**
 * @return GEAnalytics|null
 */
function get_debug_sdk()
{
    try {
        GELog::$enable = true;
        $debugConsumer = new GEDebugConsumer(SERVER_URL, 1000,);
        return new GEAnalytics($debugConsumer);
    } catch (GEDataException $e) {
        echo $e;
        return null;
    }
}

$geSDK = get_debug_sdk();
//$geSDK = get_batch_sdk();


$client_id = '_test_client_id_0';


try {
    for ($i = 0; $i < 100; $i++) {
        $properties = array();
        $properties['idx'] = $i;
        $properties['age'] = 20;
        $properties['Product_Name'] = 'c';
        $properties['update_time'] = date('Y-m-d H:i:s', time());
        $json = array();
        $json['a'] = "a";
        $json['b'] = "b";
        $jsonArray = array();
        $jsonArray[0] = $json;
        $jsonArray[1] = $json;
        $properties['json'] = $json;
        $properties['jsonArray'] = $jsonArray;
        $eventName = '$AdClick';
        $geSDK->track($client_id, $eventName, $properties);
    }

} catch (Exception $e) {
    echo $e;
}


try {
    $properties = array();
    $properties['prop_set_once'] = "once";
    $geSDK->user_set_once($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}


try {
    $properties = array();
    $properties['user_name'] = 'xxx';
    $geSDK->user_set($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}


try {
    $properties = array();
    $properties['user_name'] = '';
    $geSDK->user_unset($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

try {
    $properties = array();
    $properties['prop_list_type'] = ['a', 'a', 'b'];
    $geSDK->user_append($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}


try {
    $properties = array();
    $properties['prop_list_type'] = ['a', 'a', 'b', 'c'];
    $geSDK->user_uniq_append($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}


try {
    $properties = array();
    $properties['TotalRevenue'] = 100;
    $geSDK->user_increment($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

try {
    $properties = array();
    $properties['TotalRevenue'] = 1000;
    $geSDK->user_max($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

try {
    $properties = array();
    $properties['TotalRevenue'] = 1;
    $geSDK->user_min($client_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}


try {
    $geSDK->user_del($client_id);
} catch (Exception $e) {
    //handle except
    echo $e;
}


$geSDK->flush();

try {
    $geSDK->close();
} catch (Exception $e) {
    echo 'error' . PHP_EOL;
}