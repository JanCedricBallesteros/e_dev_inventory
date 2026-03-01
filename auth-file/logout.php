<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
include DOMAIN_PATH . '/config/config.php';
include GLOBAL_FUNC;
include CONNECT_PATH;
include CL_SESSION_PATH;
include ISLOGIN;
include API_PATH;
include JWT_PATH;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $file_data = file_get_contents("php://input", true);
    $curl_data = json_decode($file_data);
    $system_access = $curl_data->system_access;
    $system_key = $system_access;
    $data = jwt_decode_bearer(SECRET_KEY);

    ## check if the system_access is not equal to required system access [if true, return the process]
    if ($system_access != GLOBAL_SYSTEM_ACCESS) {
        echo json_encode(['response_status' => 0, 'msg_response' => 'Invalid System Access']);
        exit();
    }

    ## check if the data is empty to required system access [if true, return the process]
    if (empty($data)) {
        echo json_encode(['response_status' => 0, 'msg_response' => 'Invalid Data']);
        exit();
    }

    if (isset($data['action']) && $data['action'] == 'logout') {
        $user_id = isset($data['user_id']) ? trim($data['user_id']) : '';
        $ip = isset($data['ip']) ? trim($data['ip']) : '';
        $token_ids = isset($data['token_ids']) ? json_decode($data['token_ids']) : '';


        ## check if the user_id or token_ids is empty [if true, return the process]
        if (empty($user_id) || empty($token_ids)) {
            echo json_encode(['response_status' => 0, 'msg_response' => 'Invalid Data']);
            exit();
        }

        ## set each array data into string [('sample')]
        $array_token_ids = $token_ids[0];

        $role = 0;
        if ($g_user_role == "ADMIN"){
            $role = 1;
        }elseif ($g_user_role == "REGISTRAR"){
            $role = 2;
        }elseif ($g_user_role == "VPAA"){
            $role = 3;
        }elseif ($g_user_role == "OFFICIAL"){
            $role = 4;
        }elseif ($g_user_role == "FACULTY"){
            $role = 5;
        }elseif ($g_user_role == "STUDENT"){
            $role = 6;
        }

        ## update login_flag
        $default_query = user_log(array("ID_USER" => $user_id, "IP" => $ip, "TOKEN" => $array_token_ids, "ACTION" => "LOGOUT", "AGENTS" => "", "SUMMARY" => "", "USER_ROLE" => $role));
        if ($default_query) {
            echo json_encode(['response_status' => 1, 'msg_response' => 'success']);
            exit();
        } else {
            echo json_encode(['response_status' => 0, 'msg_response' => "Updating Failed"]);
            exit();
        }
    }

    echo json_encode(['response_status' => 0, 'msg_response' => 'Request Error, please try again']);
    exit();
}

echo json_encode(['response_status' => 0, 'msg_response' => 'Access Denied']);
exit();
