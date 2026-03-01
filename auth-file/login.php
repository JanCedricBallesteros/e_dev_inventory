<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
include DOMAIN_PATH . '/config/config.php';
include GLOBAL_FUNC;
include CL_SESSION_PATH;
include CONNECT_PATH;
include API_PATH;

$targerLink = SYSTEM_ACCESS['E-GURO++']['link']['main'];

if (isset($_GET['token']) && $_GET['token'] != '') {
    $system_token = trim($_GET['token']);

    $decoded_data = "";
    try {
        $decoded_data = custom_decrypted_string(PUBLIC_KEY, $system_token);
    } catch (Exception $e) {
        $response = base64_encode("Invalid Token. Please log in again to continue.");
        header('Location: ' . $targerLink . '?token-response=' . $response);
        exit();
    }
    $data = explode(',', $decoded_data);

    $ref_id = trim($data[0]);
    $role = trim($data[1]);
    $token_date = trim($data[2]);
    $token_auth = trim($data[3]);
    $token_id = trim($data[4]);
    $device = base64_decode($data[5]);
    $ip = base64_decode($data[6]);

    $log_status = false;
    $user_id = "";
    $general_id = "";
    $username = "";
    $f_name = "";
    $m_name = "";
    $l_name = "";
    $suffix = "";
    $fullname = "";
    $img = "";
    $user_role = "";
    $position = "";
    $assign_id = "";
    $office_id = "";
    $office_name = "";
    $sign_id = "";
    $school_year_id = "";

    if (empty($ref_id) || empty($role) || empty($token_date) || empty($token_auth) || empty($token_id) || empty($device)) {
        $response = base64_encode("Invalid Token Data. Please log in again to continue.");
        header('Location: ' . $targerLink . '?token-response=' . $response);
        exit();
    }

    if (!validateDate($token_date, "Y-m-d H:i:s")) {
        $response = base64_encode("Invalid Token. Please log in again to continue.");
        header('Location: ' . $targerLink . '?token-response=' . $response);
        exit();
    }

    if (DATE_TIME > $token_date) {
        $response = base64_encode("Token Expired. Please log in again to continue.");
        header('Location: ' . $targerLink . '?token-response=' . $response);
        exit();
    }

    if ($token_auth != SYSTEM_ACCESS[GLOBAL_SYSTEM_ACCESS]['auth']) {
        $response = base64_encode("Invalid Token Access. Please log in again to continue.");
        header('Location: ' . $targerLink . '?token-response=' . $response);
        exit();
    }

    $default_query = "SELECT user_id,general_id,username,password,f_name,m_name,l_name,suffix,img,user_role,status,locked,position,sex FROM users WHERE user_id = '" . escape($db_connect, $ref_id) . "' LIMIT 1";
    if ($query = call_mysql_query($default_query)) {
        if ($num = call_mysql_num_rows($query)) {
            while ($data = call_mysql_fetch_array($query)) {
                $user_id = $data['user_id'];
                $general_id = $data['general_id'];
                $username = $data['username'];
                $f_name = $data['f_name'];
                $m_name = $data['m_name'];
                $l_name = $data['l_name'];
                $suffix = $data['suffix'];
                $fullname = $f_name  . " " . ($m_name != '' ? substr($m_name, 0, 1) . '. ' : '') . $l_name . " " . $suffix;
                $shortname = substr($f_name, 0, 1) . '.' . ' ' . ($m_name != '' || $m_name != null ? substr($m_name, 0, 1) . '.' : '') . ' ' . $l_name . ' ' . $suffix;
                $img = !empty($data['img']) ? $data['img'] : DEFAULT_IMG;
                $user_role = $data['user_role'];
                $position = $data['position'];
                $office_id = $data['office_id'];
                $office_name = $data['name'];
                $sign_id = $data['user_sign'];
                $sex = $data['sex'];
            }
        }
    }

    #no user found
    if (empty($user_id)) {
        $response = base64_encode("Invalid Token Access. Please log in again to continue.");
        header('Location: ' . $targerLink . '?token-response=' . $response);
        exit();
    }

    ##flagged FY
    $default_query = "SELECT * FROM school_year WHERE flag_used = 1 LIMIT 1";
    if ($query = call_mysql_query($default_query)) {
        if ($num = call_mysql_num_rows($query)) {
            while ($data = call_mysql_fetch_array($query)) {
                $school_year_id = $data['school_year_id'];
            }
        }
    }
    
    try {

        $log_status = user_log(array("ID_USER" => $ref_id, "IP" => $ip, "TOKEN" => $token_id, "ACTION" => "LOGIN", "AGENTS" => $device, "SUMMARY" => "", "USER_ROLE" => $role));
        if ($log_status === false) {
            $response = base64_encode("Failed to log in. Please try again.");
            header('Location: ' . $targerLink . '?token-response=' . $response);
            exit();
        }

        if ($log_status === 'duplicate') {
            $response = base64_encode("Failed to log in. Token Already use.");
            header('Location: ' . $targerLink . '?token-response=' . $response);
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $response = base64_encode("Token has already been used. Please log in again to continue.");
            header('Location: ' . $targerLink . '?token-response=' . $response);
            exit();
        } else {
            $response = base64_encode($e->getMessage());
            header('Location: ' . $targerLink . '?token-response=' . $response);
            exit();
        }
    }

    if ($log_status) {
        $role_id = SYSTEM_ACCESS[GLOBAL_SYSTEM_ACCESS]['role'][$role];

        ## set sessions [add/change based on need]
        $session_class->setValue('user_id', $user_id);
        $session_class->setValue('user_role', $role_id);
        $session_class->setValue('general_id', $general_id);
        $session_class->setValue('fullname', $fullname);
        $session_class->setValue('shortname', $shortname);
        $session_class->setValue('sex', $sex);
        $session_class->setValue('position', $position);
        $session_class->setValue('photo', $img);
        $session_class->setValue('token_id', $token_id);
        $session_class->setValue('agent_browser', $device);
        $session_class->setValue('browser_fingerprint', $system_token);

        ## redirect link
        header("location: " . BASE_URL . "/index.php"); ## change based on direct path
        exit();
    }

    $response = base64_encode("Request Denied.");
    header('Location: ' . $targerLink . '?token-response=' . $response);
    exit();
} else {
    $response = base64_encode("Access Denied.");
    header('Location: ' . $targerLink . '?token-response=' . $response);
    exit();
}
