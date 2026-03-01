<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
include DOMAIN_PATH . '/config/config.php';
include GLOBAL_FUNC;
include CONNECT_PATH;
include API_PATH;
include JWT_PATH;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $file_data = file_get_contents("php://input", true);
    $curl_data = json_decode($file_data);
    $system_access = trim($curl_data->system_access);
    $system_key = $system_access;
    $initial_data = decode_bearer(SECRET_KEY);
    $to_encode = array();
    $output = ['response_status' => 0, 'curl_response' => '', 'system_access' => $system_access];

    if ($system_access !== GLOBAL_SYSTEM_ACCESS) {
        echo json_encode($output);
        exit();
    }

    if (empty($initial_data)) {
        echo json_encode($output);
        exit();
    }


    foreach ($initial_data as $data) {

        $general_id = isset($data->general_id) ? trim($data->general_id) : '';
        $f_name = isset($data->first_name) ? trim($data->first_name) : '';
        $m_name = isset($data->middle_name) ? trim($data->middle_name) : '';
        $l_name = isset($data->last_name) ? trim($data->last_name) : '';
        $suffix = isset($data->suffix) ? trim($data->suffix) : '';
        $birth_date = isset($data->birth_date) ? trim($data->birth_date) : '';
        $sex = isset($data->sex) ? trim($data->sex) : '';
        $user_role = isset($data->system_role) ? trim($data->system_role) : '';
        $username = isset($data->username) ? trim($data->username) : '';
        $password = isset($data->password) ? trim($data->password) : '';
        $email = isset($data->email) ? trim($data->email) : '';
        $position = isset($data->position) ? trim($data->position) : '';

        if (empty($general_id) || empty($f_name) || empty($l_name) || empty($user_role) || empty($username) || empty($email) || empty($position)) {
            $data = ['general_id' => '', 'system_id' => ''];
            $to_encode[] = ['msg_response' => "INVALID REQUIRED DATA", 'data' => $data];
            continue;
        }

        $exist_flag = false;
        $db_user_id = "";
        $db_user_role = array();
        $role = array();
        $default_select = "SELECT user_id,general_id,user_role FROM users WHERE general_id = '" . escape($db_connect, $general_id) . "' ";
        if ($select = call_mysql_query($default_select)) {
            if ($num = call_mysql_num_rows($select)) {
                $exist_flag = true;
                if ($value = call_mysql_fetch_array($select)) {
                    $db_user_id = $value['user_id'];
                    $db_user_role = json_decode($value['user_role']);
                }
            }
        }

        if ($exist_flag === true) {
            $default_select = "SELECT user_id,general_id FROM users WHERE general_id = '" . escape($db_connect, $general_id) . "' AND JSON_CONTAINS(user_role, '\"" . escape($db_connect, $user_role) . "\"')";
            if ($select = call_mysql_query($default_select)) {
                if ($num = call_mysql_num_rows($select)) {
                    $default_query = "UPDATE users SET general_id = '" . escape($db_connect, $general_id) . "', f_name = '" . escape($db_connect, $f_name) . "', m_name = '" . escape($db_connect, $m_name) . "', l_name = '" . escape($db_connect, $l_name) . "', suffix = '" . escape($db_connect, $suffix) . "', birth_date = '" . escape($db_connect, $birth_date) . "', sex = '" . escape($db_connect, $sex) . "', username = '" . escape($db_connect, $username) . "', password = '" . escape($db_connect, $password) . "', email_address = '" . escape($db_connect, $email) . "', position = '" . escape($db_connect, $position) . "' WHERE general_id = '" . escape($db_connect, $general_id) . "'";
                    if ($query = call_mysql_query($default_query)) {
                        $to_encode[] = ['msg_response' => "SUCCESS", 'system_id' => $db_user_id, 'general_id' => $general_id, 'user_role' => $user_role];
                        continue;
                    } else {
                        $to_encode[] = ['msg_response' => "FAILED", 'system_id' => '', 'general_id' => $general_id, 'user_role' => $user_role];
                        continue;
                    }
                } else {
                    array_push($db_user_role, $user_role);
                    $default_query = "UPDATE users SET general_id = '" . escape($db_connect, $general_id) . "', f_name = '" . escape($db_connect, $f_name) . "', m_name = '" . escape($db_connect, $m_name) . "', l_name = '" . escape($db_connect, $l_name) . "', suffix = '" . escape($db_connect, $suffix) . "', birth_date = '" . escape($db_connect, $birth_date) . "', sex = '" . escape($db_connect, $sex) . "', user_role = '" . json_encode($db_user_role) . "', username = '" . escape($db_connect, $username) . "', password = '" . escape($db_connect, $password) . "', email_address = '" . escape($db_connect, $email) . "', position = '" . escape($db_connect, $position) . "' WHERE general_id = '" . escape($db_connect, $general_id) . "'";
                    if ($query = call_mysql_query($default_query)) {
                        $to_encode[] = ['msg_response' => "SUCCESS", 'system_id' => $db_user_id, 'general_id' => $general_id, 'user_role' => $user_role];
                        continue;
                    } else {
                        $to_encode[] = ['msg_response' => "FAILED", 'system_id' => '', 'general_id' => $general_id, 'user_role' => $user_role];
                        continue;
                    }
                }
            }
        } else {
            $role = json_encode(array($user_role));
            $sql = "INSERT INTO users (general_id,f_name,m_name,l_name,suffix,sex,birth_date,user_role,username,password,email_address,position) VALUES ('" . escape($db_connect, $general_id) . "','" . escape($db_connect, $f_name) . "','" . escape($db_connect, $m_name) . "','" . escape($db_connect, $l_name) . "','" . escape($db_connect, $suffix) . "','" . escape($db_connect, $sex) . "','" . escape($db_connect, $birth_date) . "','" . $role . "','" . escape($db_connect, $username) . "','" . escape($db_connect, $password) . "','" . escape($db_connect, $email) . "','" . escape($db_connect, $position) . "')";
            if ($query = call_mysql_query($sql)) {
                $system_id = $db_connect->insert_id;
                $to_encode[] = ['msg_response' => "SUCCESS", 'system_id' => $system_id, 'general_id' => $general_id, 'user_role' => $user_role];
                continue;
            } else {
                $to_encode[] = ['msg_response' => "FAILED", 'system_id' => '', 'general_id' => $general_id, 'user_role' => $user_role];
                continue;
            }
        }
    }

    if (!empty($to_encode)) {
        $encrypted_data = encrypted_data(SECRET_KEY, json_encode($to_encode));
        // $jwt_encode = JWT::encode($to_encode, SECRET_KEY, JWT_ALG);
        $output = ['response_status' => 1, 'curl_response' => $encrypted_data, 'system_access' => $system_access];
    }
    echo json_encode($output);
    exit();
}


echo json_encode([
    'response_status' => 0,
    'msg_response' => 'Access Denied'
]);
exit();
