<?php
// admin/modules/nonconsumable/process/ast_physical_checking_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$isStaffAst = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("AST", "PO")));
$isAdmin = role_has("ADMIN");
if (!isset($g_user_role) || (!$isAdmin && !$isStaffAst)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit();
}

function _post($k, $default = '') {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default;
}
function _int($v, $default = 0) {
    if ($v === '' || $v === null) return $default;
    return (int)$v;
}
function _esc($v) {
    global $conn;
    $v = (string)$v;
    if (isset($conn) && $conn instanceof mysqli) {
        return mysqli_real_escape_string($conn, $v);
    }
    return addslashes($v);
}
function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

$action = _post('action', '');
if ($action === '') {
    json_response(['success' => false, 'message' => 'Missing action.'], 400);
}

try {
    switch ($action) {
        case 'create_session': {
            if (!$isAdmin) {
                json_response(['success' => false, 'message' => 'Access denied.'], 403);
            }
            $audit_name = _post('audit_name');
            $start_date = _post('start_date');
            $end_date = _post('end_date');
            $status = _post('status', 'Pending');
            if ($start_date === '' || $end_date === '') {
                json_response(['success' => false, 'message' => 'Start and end dates are required.'], 422);
            }
            if (!in_array($status, ['Pending', 'Active', 'Closed'], true)) {
                $status = 'Pending';
            }
            $year = date('Y');
            $prefix = "PCAST-{$year}-";
            $sqlMax = "SELECT MAX(CAST(RIGHT(series_code,2) AS UNSIGNED)) AS maxnum
                       FROM ast_audit_sessions
                       WHERE series_code LIKE '{$prefix}%'";
            $resMax = call_mysql_query($sqlMax);
            $max = 0;
            if ($resMax && ($r = call_mysql_fetch_array($resMax))) {
                $max = (int)($r['maxnum'] ?? 0);
            }
            $next = str_pad((string)($max + 1), 2, '0', STR_PAD_LEFT);
            $series_code = $prefix . $next;

            $sql = "INSERT INTO ast_audit_sessions
                    (series_code, audit_name, start_date, end_date, status, created_by, created_at)
                    VALUES (
                        '" . _esc($series_code) . "',
                        " . ($audit_name !== '' ? "'" . _esc($audit_name) . "'" : "NULL") . ",
                        '" . _esc($start_date) . "',
                        '" . _esc($end_date) . "',
                        '" . _esc($status) . "',
                        " . (int)$GLOBALS['s_user_id'] . ",
                        NOW()
                    )";
            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to create session.'], 500);
            }
            activity_log_new("AST PHYSICAL CHECK SESSION CREATE", "SUCCESS", array(
                'series_code' => $series_code,
                'audit_name' => $audit_name,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status
            ));
            json_response(['success' => true, 'message' => 'Session created.', 'series_code' => $series_code]);
            break;
        }

        case 'list_sessions': {
            $sql = "SELECT s.*, u.f_name, u.l_name
                    FROM ast_audit_sessions s
                    LEFT JOIN users u ON u.user_id = s.created_by
                    ORDER BY s.created_at DESC";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['created_by_name'] = trim(($row['f_name'] ?? '') . ' ' . ($row['l_name'] ?? ''));
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;
        }

        case 'list_active_sessions': {
            $sql = "SELECT id, series_code, audit_name, start_date, end_date
                    FROM ast_audit_sessions
                    WHERE status = 'Active'
                    ORDER BY created_at DESC";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;
        }

        case 'update_session_status': {
            if (!$isAdmin) {
                json_response(['success' => false, 'message' => 'Access denied.'], 403);
            }
            $session_id = _int(_post('session_id'));
            $status = _post('status');
            if ($session_id <= 0 || !in_array($status, ['Pending', 'Active', 'Closed'], true)) {
                json_response(['success' => false, 'message' => 'Invalid request.'], 422);
            }
            $prevRes = call_mysql_query("SELECT series_code, status FROM ast_audit_sessions WHERE id = {$session_id} LIMIT 1");
            $prevRow = $prevRes ? call_mysql_fetch_array($prevRes) : null;
            $sql = "UPDATE ast_audit_sessions SET status = '" . _esc($status) . "' WHERE id = {$session_id} LIMIT 1";
            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to update status.'], 500);
            }
            activity_log_new("AST PHYSICAL CHECK SESSION STATUS", "SUCCESS", array(
                'session_id' => $session_id,
                'series_code' => $prevRow['series_code'] ?? null,
                'old_status' => $prevRow['status'] ?? null,
                'new_status' => $status
            ));
            json_response(['success' => true, 'message' => 'Status updated.']);
            break;
        }

        case 'get_item_by_code': {
            $property_code = _post('property_code');
            if ($property_code === '') {
                json_response(['success' => false, 'message' => 'Property code is required.'], 422);
            }
            $sql = "SELECT i.*, c.item_category_name, c.category_photo
                    FROM ast_inventory i
                    LEFT JOIN ast_inventory_category c ON c.category_id = i.category_id
                    WHERE i.property_code = '" . _esc($property_code) . "'
                    LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }
            $row['category_photo_url'] = $row['category_photo']
                ? BASE_URL . 'upload/category/' . $row['category_photo']
                : null;
            $row['category_photo_thumb_url'] = $row['category_photo']
                ? BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($row['category_photo']) . '&s=100'
                : null;
            json_response(['success' => true, 'data' => $row]);
            break;
        }

        case 'create_check': {
            $session_id = _int(_post('session_id'));
            $property_code = _post('property_code');
            $quantity_checked = 1;
            $serial_number = _post('serial_number');
            $remarks = _post('remarks');
            $condition = _post('condition');
            $status_at_check = _post('status_at_check');
            $facility = _post('facility');
            $accountable = _post('accountable');
            $issued_to = _post('issued_to');
            $managed_by = _post('managed_by');
            $date_issued = _post('date_issued');

            if ($session_id <= 0 || $property_code === '') {
                json_response(['success' => false, 'message' => 'Session and property code are required.'], 422);
            }

            $resSession = call_mysql_query("SELECT status FROM ast_audit_sessions WHERE id = {$session_id} LIMIT 1");
            $session = $resSession ? call_mysql_fetch_array($resSession) : null;
            if (!$session || ($session['status'] ?? '') !== 'Active') {
                json_response(['success' => false, 'message' => 'Only active sessions allow checking.'], 422);
            }

            $resItem = call_mysql_query("SELECT * FROM ast_inventory WHERE property_code = '" . _esc($property_code) . "' LIMIT 1");
            $item = $resItem ? call_mysql_fetch_array($resItem) : null;
            if (!$item) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }

            $property_id = (int)($item['item_id'] ?? 0);
            $property_number = $item['property_number'] ?? '';
            $item_description = $item['item_description'] ?? '';
            $unit = $item['unit'] ?? '';
            $date_stock = $item['created_at'] ?? null;
            if ($serial_number === '' && isset($item['serial_number'])) {
                $serial_number = trim((string)$item['serial_number']);
            }
            $checked_by = (int)$GLOBALS['s_user_id'];

            $existing = call_mysql_query("SELECT id FROM ast_audit_checks WHERE session_id = {$session_id} AND property_code = '" . _esc($property_code) . "' LIMIT 1");
            $existingRow = $existing ? call_mysql_fetch_array($existing) : null;

            if ($existingRow) {
                $prevRes = call_mysql_query("SELECT * FROM ast_audit_checks WHERE id = " . (int)$existingRow['id'] . " LIMIT 1");
                $prev = $prevRes ? call_mysql_fetch_array($prevRes) : null;
                $sql = "UPDATE ast_audit_checks SET
                            serial_number = '" . _esc($serial_number) . "',
                            quantity_checked = {$quantity_checked},
                            unit = '" . _esc($unit) . "',
                            date_stock = " . ($date_stock ? "'" . _esc($date_stock) . "'" : "NULL") . ",
                            date_issued = " . ($date_issued !== '' ? "'" . _esc($date_issued) . "'" : "NULL") . ",
                            status_at_check = '" . _esc($status_at_check) . "',
                            facility = '" . _esc($facility) . "',
                            accountable = '" . _esc($accountable) . "',
                            issued_to = '" . _esc($issued_to) . "',
                            managed_by = '" . _esc($managed_by) . "',
                            `condition` = '" . _esc($condition) . "',
                            remarks = '" . _esc($remarks) . "',
                            checked_by = {$checked_by},
                            checked_at = NOW()
                        WHERE id = " . (int)$existingRow['id'] . " LIMIT 1";
            } else {
                $sql = "INSERT INTO ast_audit_checks
                        (session_id, property_id, property_code, property_number, item_description, serial_number,
                         quantity_checked, unit, date_stock, date_issued, status_at_check, facility, accountable,
                         issued_to, managed_by, `condition`, remarks, checked_by, checked_at)
                        VALUES (
                            {$session_id},
                            {$property_id},
                            '" . _esc($property_code) . "',
                            '" . _esc($property_number) . "',
                            '" . _esc($item_description) . "',
                            '" . _esc($serial_number) . "',
                            {$quantity_checked},
                            '" . _esc($unit) . "',
                            " . ($date_stock ? "'" . _esc($date_stock) . "'" : "NULL") . ",
                            " . ($date_issued !== '' ? "'" . _esc($date_issued) . "'" : "NULL") . ",
                            '" . _esc($status_at_check) . "',
                            '" . _esc($facility) . "',
                            '" . _esc($accountable) . "',
                            '" . _esc($issued_to) . "',
                            '" . _esc($managed_by) . "',
                            '" . _esc($condition) . "',
                            '" . _esc($remarks) . "',
                            {$checked_by},
                            NOW()
                        )";
            }

            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to save check.'], 500);
            }
            activity_log_new($existingRow ? "AST PHYSICAL CHECK UPDATE" : "AST PHYSICAL CHECK CREATE", "SUCCESS", array(
                'session_id' => $session_id,
                'property_code' => $property_code,
                'property_number' => $property_number,
                'item_description' => $item_description,
                'quantity_checked' => $quantity_checked,
                'unit' => $unit,
                'serial_number' => $serial_number,
                'status_at_check' => $status_at_check,
                'facility' => $facility,
                'accountable' => $accountable,
                'issued_to' => $issued_to,
                'managed_by' => $managed_by,
                'condition' => $condition,
                'remarks' => $remarks,
                'date_issued' => $date_issued,
                'previous' => isset($prev) && is_array($prev) ? $prev : null
            ));
            json_response(['success' => true, 'message' => 'Check saved.']);
            break;
        }

        case 'list_checks': {
            $session_id = _int(_post('session_id'));
            $search = _post('search');
            $where = [];
            if ($session_id > 0) {
                $where[] = "c.session_id = {$session_id}";
            }
            if ($search !== '') {
                $searchEsc = _esc('%' . $search . '%');
                $where[] = "(c.property_code LIKE '{$searchEsc}' OR c.item_description LIKE '{$searchEsc}')";
            }
            $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

            $sql = "SELECT c.*, s.series_code, u.f_name, u.l_name
                    FROM ast_audit_checks c
                    LEFT JOIN ast_audit_sessions s ON s.id = c.session_id
                    LEFT JOIN users u ON u.user_id = c.checked_by
                    {$whereSql}
                    ORDER BY c.checked_at DESC";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['checked_by_name'] = trim(($row['f_name'] ?? '') . ' ' . ($row['l_name'] ?? ''));
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;
        }

        default:
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_response(['success' => false, 'message' => 'Server error occurred.'], 500);
}
