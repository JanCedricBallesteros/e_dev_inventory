<?php
require 'config/config.php';
include CONNECT_PATH;
include GLOBAL_FUNC;
include CL_SESSION_PATH;
include ISLOGIN;
include API_PATH;

## API URL
$targerLink = SYSTEM_ACCESS['E-GURO++']['link']['main'];

## allowed roles to access 
$allowed_roles = array_values(SYSTEM_ACCESS[GLOBAL_SYSTEM_ACCESS]['role']);
if (!in_array($g_user_role, $allowed_roles, true)) {
    include HTTP_404;
    exit();
}

## get ip address
$ip = get_ip();

## update login_flag
$default_query = user_log(array("ID_USER" => $s_user_id, "IP" => $ip, "TOKEN" => $g_token_id, "ACTION" => "LOGOUT", "AGENTS" => "", "SUMMARY" => ""));
if ($default_query) {
    session_destroy();
    header('Location: ' . $targerLink); ## redirect to e-Guro++
    exit();
} else {
    echo json_encode(['response_status' => 0, 'msg_response' => "Signing out failed, please try again."]);
    header('Location: ' . BASE_URL); ## balik kung san dapat
    exit();
}

header('Location: ' . BASE_URL); ## balik kung san dapat
exit();
