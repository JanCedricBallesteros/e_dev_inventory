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
    $system_access = $curl_data->system_access;
    $system_key = $system_access;
    $data = decode_bearer(SECRET_KEY);

    if ($system_access != GLOBAL_SYSTEM_ACCESS) {
        echo json_encode(['response_status' => 0, 'msg_response' => 'Invalid System Access']);
        exit();
    }

    if (empty($data)) {
        echo json_encode(['response_status' => 0, 'msg_response' => 'Invalid Data']);
        exit();
    }

    if (isset($data->action) && $data->action == 'update_user') {
        $ref_id = isset($data->ref_id) ? trim($data->ref_id) : '';
        $general_id = isset($data->general_id) ? trim($data->general_id) : '';
        $f_name = isset($data->f_name) ? trim($data->f_name) : '';
        $m_name = isset($data->m_name) ? trim($data->m_name) : '';
        $l_name = isset($data->l_name) ? trim($data->l_name) : '';
        $suffix = isset($data->suffix) ? trim($data->suffix) : '';
        $birth_date = isset($data->birth_date) ? trim($data->birth_date) : '';
        $sex = isset($data->sex) ? trim($data->sex) : '';
        $username = isset($data->username) ? trim($data->username) : '';
        $email = isset($data->email) ? trim($data->email) : '';
        // $password = isset($data->password) ? trim($data->password) : '';
        $position = isset($data->position) ? trim($data->position) : '';

        if (empty($ref_id)) {
            echo json_encode(['response_status' => 0, 'msg_response' => 'Invalid Data']);
            exit();
        }

        $default_query = "UPDATE users SET general_id = '" . escape($db_connect, $general_id) . "', f_name = '" . escape($db_connect, $f_name) . "', m_name = '" . escape($db_connect, $m_name) . "', l_name = '" . escape($db_connect, $l_name) . "', suffix = '" . escape($db_connect, $suffix) . "', birth_date = '" . escape($db_connect, $birth_date) . "', sex = '" . escape($db_connect, $sex) . "', username = '" . escape($db_connect, $username) . "', email_address = '" . escape($db_connect, $email) . "', recovery_email = '" . escape($db_connect, $recovery_email) . "', position = '" . escape($db_connect, $position) . "' WHERE user_id = '" . escape($db_connect, $ref_id) . "'";
        if ($query = call_mysql_query($default_query)) {
            echo json_encode(['response_status' => 1, 'msg_response' => 'success']);
            exit();
        } else {
            echo json_encode(['response_status' => 0, 'msg_response' => "Updating Failed"]);
            exit();
        }
    }

    echo json_encode(['response_status' => 0, 'msg_response' => 'Request Error']);
    exit();
}


echo json_encode(['response_status' => 0, 'msg_response' => 'Access Denied']);
exit();
