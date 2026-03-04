<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

if (!(role_has("SUPER_ADMIN") || role_has("ADMIN"))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$access_json = isset($_POST['access']) ? $_POST['access'] : '[]';
$access_list = json_decode($access_json, true);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user.']);
    exit();
}
if (!is_array($access_list)) {
    $access_list = [];
}

// Only allow access assignment to ADMIN_STAFF users
$roleRes = call_mysql_query("SELECT user_role FROM users WHERE user_id = {$user_id} LIMIT 1");
$roleRow = $roleRes ? call_mysql_fetch_array($roleRes) : null;
$roleIds = $roleRow && !empty($roleRow['user_role']) ? json_decode($roleRow['user_role'], true) : [];
$roleIds = is_array($roleIds) ? $roleIds : [];
if (!(in_array(3, $roleIds, true) || in_array('3', $roleIds, true))) {
    echo json_encode(['success' => false, 'message' => 'Access can only be assigned to Admin Staff users.']);
    exit();
}

$allowed = ['CSM','AST','PO'];
$clean = [];
foreach ($access_list as $code) {
    $code = strtoupper(trim((string)$code));
    if (in_array($code, $allowed, true)) {
        $clean[] = $code;
    }
}
$clean = array_values(array_unique($clean));

call_mysql_query("DELETE FROM user_access WHERE user_id = {$user_id}");
foreach ($clean as $code) {
    $code_esc = mysqli_real_escape_string($db_connect, $code);
    $sql = "INSERT INTO user_access (user_id, access_code, is_active) VALUES ({$user_id}, '{$code_esc}', 1)";
    call_mysql_query($sql);
}

echo json_encode(['success' => true, 'message' => 'Access updated.']);
exit();

