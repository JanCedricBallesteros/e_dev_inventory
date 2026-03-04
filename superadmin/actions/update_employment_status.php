<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json');

// Check if user is super admin
if (!(role_has("SUPER_ADMIN") || role_has("ADMIN"))) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

function sa_safe_query($sql)
{
    try {
        return call_mysql_query($sql);
    } catch (Throwable $e) {
        return false;
    }
}

function sa_table_exists($table)
{
    global $db_connect;
    $tableEsc = escape($db_connect, $table);
    $res = sa_safe_query("SHOW TABLES LIKE '{$tableEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

function sa_column_exists($table, $column)
{
    global $db_connect;
    if (!sa_table_exists($table)) return false;
    $tableSafe = str_replace('`', '``', $table);
    $columnEsc = escape($db_connect, $column);
    $res = sa_safe_query("SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

// Get POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$employment_status_id = isset($_POST['employment_status_id']) ? intval($_POST['employment_status_id']) : 0;

// Validate inputs
if ($user_id <= 0 || $employment_status_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user or employment status'
    ]);
    exit();
}

if (
    !sa_table_exists('users') ||
    !sa_table_exists('employment_status') ||
    !sa_column_exists('users', 'employment_status_id') ||
    !sa_column_exists('employment_status', 'employment_status_id') ||
    !sa_column_exists('employment_status', 'status_name')
) {
    echo json_encode([
        'success' => false,
        'message' => 'Employment status update is unavailable: database schema does not include users.employment_status_id.'
    ]);
    exit();
}

// Verify employment status exists
$verify_status = "SELECT employment_status_id FROM employment_status WHERE employment_status_id = '$employment_status_id'";
$query = sa_safe_query($verify_status);

if (!$query) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: Employment status verification failed'
    ]);
    exit();
}

if (mysqli_num_rows($query) == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Employment status not found'
    ]);
    exit();
}

// Capture previous value for audit log
$prev_status_id = 0;
$prev_query = sa_safe_query("SELECT employment_status_id FROM users WHERE user_id = '$user_id' LIMIT 1");
if ($prev_query && ($prev_row = mysqli_fetch_array($prev_query))) {
    $prev_status_id = (int)($prev_row['employment_status_id'] ?? 0);
}

// Update user employment status
$update = "UPDATE users SET employment_status_id = '$employment_status_id' WHERE user_id = '$user_id'";
$result = sa_safe_query($update);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: Failed to update'
    ]);
    exit();
}

// Verify the update by checking the new value
$verify_update = "SELECT employment_status_id FROM users WHERE user_id = '$user_id' AND employment_status_id = '$employment_status_id'";
$verify_query = sa_safe_query($verify_update);

if ($verify_query && mysqli_num_rows($verify_query) > 0) {
    // Get the updated user info for activity log
    $get_user = "SELECT general_id, f_name, m_name, l_name, position FROM users WHERE user_id = '$user_id'";
    $user_query = sa_safe_query($get_user);
    
    if ($user_query && $user_data = mysqli_fetch_array($user_query)) {
        $full_name = get_full_name($user_data['f_name'], $user_data['m_name'], $user_data['l_name'], '');
        $general_id = $user_data['general_id'];
        
        // Log update without breaking API response on logging issues
        $details = array(
            'user_id' => $user_id,
            'general_id' => $general_id,
            'full_name' => $full_name,
            'old_employment_status_id' => $prev_status_id,
            'new_employment_status_id' => $employment_status_id
        );
        try {
            activity_log_new("UPDATE EMPLOYMENT STATUS:: DETAILS::" . json_encode($details));
        } catch (Throwable $e) {
            // ignore logging errors and keep JSON response valid
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Employment status updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No changes made. Please verify the selection.'
    ]);
}
exit();
?>

