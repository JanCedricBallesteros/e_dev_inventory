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
    $data = decode_bearer(SECRET_KEY);

    if ($system_access !== GLOBAL_SYSTEM_ACCESS) {
        echo json_encode([
            'response_status' => 0,
            'msg_response' => 'Invalid System Access',
        ]);
        exit();
    }

    if (empty($data)) {
        echo json_encode([
            'response_status' => 0,
            'msg_response' => 'Invalid Data',
        ]);
        exit();
    }


    if (isset($data->action) && $data->action == 'add_account') {

        $general_id = isset($data->general_id) ? trim($data->general_id) : '';
        $f_name = isset($data->f_name) ? trim($data->f_name) : '';
        $m_name = isset($data->m_name) ? trim($data->m_name) : '';
        $l_name = isset($data->l_name) ? trim($data->l_name) : '';
        $suffix = isset($data->suffix) ? trim($data->suffix) : '';
        $birth_date = isset($data->birth_date) ? trim($data->birth_date) : '';
        $sex = isset($data->sex) ? trim($data->sex) : '';
        $user_role = isset($data->user_role) ? trim($data->user_role) : '';
        $username = isset($data->username) ? trim($data->username) : '';
        $password = isset($data->password) ? trim($data->password) : '';
        $email = isset($data) ? trim($data->email) : '';
        $recovery_email = isset($data->recovery_email) ? trim($data->recovery_email) : '';
        $position = isset($data->position) ? trim($data->position) : '';


        if (empty($general_id) || empty($f_name) || empty($l_name) || empty($user_role) || empty($username) || empty($email) || empty($position)) {
            echo json_encode([
                'response_status' => 0,
                'msg_response' => 'Invalid Data'
            ]);
            exit();
        }

        $unique_id = '';
        $exist = false;
        $db_user_role = array();
        $sql_select = "SELECT user_id, general_id,user_role FROM users WHERE general_id = '" . escape($db_connect, $general_id) . "' ";
        if ($query = call_mysql_query($sql_select)) {
            if ($num = mysqli_num_rows($query)) {
                $exist = true;
                if ($data = mysqli_fetch_assoc($query)) {
                    $unique_id = $data['user_id'];
                    $db_user_role = json_decode($data['user_role']);
                }
            }
        }

        try {
            if ($exist) {
                $default_select = "SELECT user_id,general_id FROM users WHERE general_id = '" . escape($db_connect, $general_id) . "' AND JSON_CONTAINS(user_role, '\"" . escape($db_connect, $user_role) . "\"')";
                if ($select = call_mysql_query($default_select)) {
                    if ($num = call_mysql_num_rows($select)) {
                        $payload = [
                            'status' => 1,
                            'response' => 1,
                            'data' => [
                                'system_id' => $unique_id,
                                'user_role' => $user_role,
                            ]
                        ];

                        $en = encrypted_data(SECRET_KEY, json_encode($payload));
                        echo json_encode([
                            'response_status' => 1,
                            'msg_response' => 'success',
                            'curl_response' => $en
                        ]);

                        exit();
                    } else {
                        array_push($db_user_role, $user_role);
                        $default_query = "UPDATE users SET general_id = '" . escape($db_connect, $general_id) . "', f_name = '" . escape($db_connect, $f_name) . "', m_name = '" . escape($db_connect, $m_name) . "', l_name = '" . escape($db_connect, $l_name) . "', suffix = '" . escape($db_connect, $suffix) . "', birth_date = '" . escape($db_connect, $birth_date) . "', sex = '" . escape($db_connect, $sex) . "', user_role = '" . json_encode($db_user_role) . "', username = '" . escape($db_connect, $username) . "', password = '" . escape($db_connect, $password) . "', email_address = '" . escape($db_connect, $email) . "', position = '" . escape($db_connect, $position) . "' WHERE general_id = '" . escape($db_connect, $general_id) . "'";
                        if ($query = call_mysql_query($default_query)) {
                            $payload = [
                                'status' => 1,
                                'response' => 1,
                                'data' => [
                                    'system_id' => $unique_id,
                                    'user_role' => $user_role,
                                ]
                            ];

                            $en = encrypted_data(SECRET_KEY, json_encode($payload));
                            echo json_encode([
                                'response_status' => 1,
                                'msg_response' => 'success',
                                'curl_response' => $en
                            ]);

                            exit();
                        } else {
                            echo json_encode([
                                'response_status' => 0,
                                'msg_response' => 'Entry Error, please try again',
                            ]);
                            exit();
                        }
                    }
                }
            } else {
                $role = json_encode(array($user_role));
                $sql = "INSERT INTO users (general_id,f_name,m_name,l_name,suffix,birth_date,sex,user_role,username,password,email_address,recovery_email,position) VALUES ('" . escape($db_connect, $general_id) . "','" . escape($db_connect, $f_name) . "','" . escape($db_connect, $m_name) . "','" . escape($db_connect, $l_name) . "','" . escape($db_connect, $suffix) . "','" . escape($db_connect, $birth_date) . "','" . escape($db_connect, $sex) . "','" . $role . "','" . escape($db_connect, $username) . "','" . escape($db_connect, $password) . "','" . escape($db_connect, $email) . "','" . escape($db_connect, $recovery_email) . "','" . escape($db_connect, $position) . "')";
                if ($query = call_mysql_query($sql)) {
                    $payload = [
                        'status' => 1,
                        'response' => 1,
                        'data' => [
                            'system_id' => $db_connect->insert_id,
                            'user_role' => $user_role,
                        ]
                    ];

                    $en = encrypted_data(SECRET_KEY, json_encode($payload));
                    echo json_encode([
                        'response_status' => 1,
                        'msg_response' => 'success',
                        'curl_response' => $en
                    ]);

                    exit();
                } else {
                    echo json_encode([
                        'response_status' => 0,
                        'msg_response' => 'Entry Error, please try again',
                    ]);
                    exit();
                }
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                echo json_encode([
                    'response_status' => 0,
                    'msg_response' => 'Duplicated Entry',
                ]);
                exit();
            } else {
                echo json_encode([
                    'response_status' => 0,
                    'msg_response' => $e->getMessage(),
                ]);
                exit();
            }
        }

        echo json_encode([
            'response_status' => 0,
            'msg_response' => 'Request Error',
        ]);

        exit();
    }
}


echo json_encode([
    'response_status' => 0,
    'msg_response' => 'Access Denied'
]);
exit();
