<?php

$api_url =  api_base_url();
define('API_BASE_URL', $api_url);
define('SYSTEM_ACCES', '');
define('PUBLIC_KEY', '43e5db0dff817bb6540246f2ecfc5b02b3accd6b9ac90a3b04216a7f98c036ed');
define('SECRET_KEY', 'bkM4MXIvbmMyM1pzMTIvQmY1V3lCUkFRcnFQOUNKK1Q5cTV5SVVCUTJ1cExnTWNtbDlwSHhoWk9GQVdzNlJET1VGMERRYTF5OEZHeVRSSTVJZFB2R1BMMTFTcVZlY01uTEF3aytiREg0NFE9OjpC6qtnYWk3YyOUpGQ_SLASH_ubEH');
define('JWT_ALG', 'HS256');
define('API_URL_LOGIN', API_BASE_URL . 'auth-file/login-user.php'); // login, locked, reset password

function curl_request($url, $system_access, $jwt_data)
{
    $system_access = json_encode($system_access);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $system_access);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Authorization: Bearer ' . $jwt_data
    ));
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    $responses = curl_exec($curl);
    $error_msg = '';
    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }

    curl_close($curl);

    if (!empty($error_msg)) {
        $response = array("error_msg" => $error_msg);
        $responses = json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
    }

    return $responses;
}

function system_key($system_access)
{
    global $db_connect;
    $system_key = array();
    $system_sql = "SELECT `public_key`,`secret_key` FROM `system_key` WHERE `system_type`='" . escape($db_connect, $system_access) . "' LIMIT 1";
    if ($system_query = mysqli_query($db_connect, $system_sql)) {
        if ($system_num_row = mysqli_num_rows($system_query)) {
            while ($system_data = call_mysql_fetch_array($system_query)) {
                $system_data = array_html($system_data);
                $system_key['public_key'] = $system_data['public_key'];
                $system_key['secret_key'] = $system_data['secret_key'];
            }
        }
    }
    return $system_key;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function jwt_decode_bearer($system_key) //decode jwt data with authorization bearer
{
    $jwt_data = null;
    $data = array();
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        $jwt_data = str_replace('Bearer ', '', $auth_header);
    }



    // validate the JWT data
    if ($jwt_data === null) {
        $response = array("error_msg" => 'No data Found');
        return $response;
    }

    $decoded = array();
    try {
        $decoded = JWT::decode($jwt_data, new Key($system_key, JWT_ALG));
    } catch (Exception $e) {
        $response = array("error_msg" => 'Error Encounter');
        return $response;
        exit();
    }

    $data = (array) $decoded;
    return $data;
}

function decode_bearer($system_key) //decode jwt data with authorization bearer
{
    $en_data = null;
    $data = array();
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        $en_data = str_replace('Bearer ', '', $auth_header);
    }

    if ($en_data === null) {
        $response = array("error_msg" => 'No data Found');
        return $response;
    }

    $decoded = array();
    try {
        $decoded = decrypted_data($system_key, $en_data);
    } catch (Exception $e) {
        $response = array("error_msg" => 'Error Encounter');
        return $response;
        exit();
    }

    $data = json_decode($decoded);
    return $data;
}

## not used
// URL for API
function api_base_url()
{
    // first get http protocol if http or https
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
    $base_url .= "https://eguro/"; #change to localhost or domain
    return $base_url;
}
