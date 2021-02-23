<?php

function apiRequestWebhook($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string", 3, "../logs/bot.log");
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array", 3, "../logs/bot.log");
        return false;
    }

    $parameters["method"] = $method;

    $payload = json_encode($parameters);
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($payload));
    echo $payload;

    return true;
}

function curlRequest($handle)
{
    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error", 3, "../logs/bot.log");
        curl_close($handle);
        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);

    if ($http_code >= 500) {
        sleep(10); // do not wat to DDOS server if something goes wrong
        return false;
    } else if ($http_code != 200) {
        $response = json_decode($response, true);
        error_log("Request has failed with error {$response['error_code']}: {$response['description']}", 3, "../logs/bot.log");
        if ($http_code == 401) {
            throw new Exception('Invalid access token provided');
        }
        return false;
    } else {
        $response = json_decode($response, true);
        //if (isset($response['description'])) {
            //error_log("Request was successful: {$response['description']}\n", 3, "../logs/bot.log");
        //}
        $response = $response['result'];
    }

    return $response;
}

function apiRequest($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string", 3, "../logs/bot.log");
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array", 3, "../logs/bot.log");
        return false;
    }

    foreach ($parameters as $key => &$val) {
        // encoding to JSON array parameters, for example reply_markup
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }
    $url = API_URL . $method . '?' . http_build_query($parameters);

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);

    return curlRequest($handle);
}

function apiRequestJson($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string", 3, "../logs/bot.log");        
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array", 3, "../logs/bot.log");
        return false;
    }

    $parameters["method"] = $method;

    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

    return curlRequest($handle);
}

function apiRequestFile($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string", 3, "../logs/bot.log");        
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array", 3, "../logs/bot.log");
        return false;
    }

    $parameters["method"] = $method;

    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));

    return curlRequest($handle);
}

if (php_sapi_name() == 'cli') {
    apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
    exit;
}