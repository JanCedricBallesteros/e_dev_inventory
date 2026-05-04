<?php
// admin/modules/consumable/process/csm_physical_checking_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$isStaffCsm = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access("CSM"));
$isAdmin = role_has("ADMIN");

if (!isset($g_user_role) || (!$isAdmin && !$isStaffCsm)) {
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

function get_last_insert_id_safe() {
    $res = call_mysql_query("SELECT LAST_INSERT_ID() AS id");
    $row = $res ? call_mysql_fetch_array($res) : null;
    return (int)($row['id'] ?? 0);
}

function build_upload_url($pathOrFile) {
    $pathOrFile = trim((string)$pathOrFile);
    if ($pathOrFile === '') return null;

    if (preg_match('~^(https?:)?//~i', $pathOrFile)) {
        return $pathOrFile;
    }

    $clean = ltrim($pathOrFile, '/');

    if (strpos($clean, 'upload/') === 0) {
        return BASE_URL . $clean;
    }

    return BASE_URL . 'upload/category/' . $clean;
}

function build_thumb_url($pathOrFile) {
    $pathOrFile = trim((string)$pathOrFile);
    if ($pathOrFile === '') return null;

    $fileName = basename($pathOrFile);
    if ($fileName === '') return null;

    return BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($fileName) . '&s=100';
}

function derive_csm_stock_label($row) {
    $status = isset($row['status']) ? (int)$row['status'] : 1;
    $currentQty = (int)($row['current_quantity'] ?? 0);
    $critLevel = (int)($row['qty_crit_level'] ?? 0);

    if ($status !== 1) {
        return 'Unavailable';
    }
    if ($currentQty <= 0) {
        return 'Out of Stock';
    }
    if ($critLevel > 0 && $currentQty <= $critLevel) {
        return 'Critical Stock';
    }
    return 'Available';
}

function derive_session_status($startDate, $endDate) {
    $today = date('Y-m-d');
    $start = trim((string)$startDate);
    $end = trim((string)$endDate);

    if ($start === '' || $end === '') return 'Pending';
    if ($today < $start) return 'Pending';
    if ($today > $end) return 'Closed';
    return 'Active';
}

function ensure_single_active_session($ignoreSessionId = 0) {
    $ignore = (int)$ignoreSessionId;
    $whereIgnore = $ignore > 0 ? "AND id <> {$ignore}" : "";
    $today = date('Y-m-d');
    $res = call_mysql_query("SELECT id, series_code
                             FROM csm_audit_sessions
                             WHERE start_date <= '" . _esc($today) . "'
                               AND end_date >= '" . _esc($today) . "'
                             {$whereIgnore}
                             ORDER BY created_at DESC
                             LIMIT 1");
    $row = $res ? call_mysql_fetch_array($res) : null;
    return $row ?: null;
}

function sync_session_status($sessionId, $startDate, $endDate, $storedStatus = null) {
    return derive_session_status($startDate, $endDate);
}

function resolve_csm_item_image($item) {
    $categoryId = (int)($item['category_id_ref'] ?? 0);
    $categoryImageId = (int)($item['category_image_id'] ?? 0);
    $itemCategoryImg = trim((string)($item['item_category_img'] ?? ''));
    $categoryPhoto = trim((string)($item['category_photo'] ?? ''));

    if ($categoryImageId <= 0 && $itemCategoryImg !== '' && ctype_digit($itemCategoryImg)) {
        $categoryImageId = (int)$itemCategoryImg;
    }

    $resolved = '';

    if ($categoryImageId > 0) {
        $imgRes = call_mysql_query("
            SELECT file_name, file_url
            FROM csm_inventory_category_images
            WHERE image_id = {$categoryImageId}
            LIMIT 1
        ");
        $imgRow = $imgRes ? call_mysql_fetch_array($imgRes) : null;

        if ($imgRow) {
            $resolved = trim((string)($imgRow['file_url'] ?? ''));
            if ($resolved === '') {
                $resolved = trim((string)($imgRow['file_name'] ?? ''));
            }
        }
    }

    if ($resolved === '' && $itemCategoryImg !== '' && !ctype_digit($itemCategoryImg)) {
        $resolved = $itemCategoryImg;
    }

    if ($resolved === '' && $categoryId > 0) {
        $imgRes = call_mysql_query("
            SELECT file_name, file_url
            FROM csm_inventory_category_images
            WHERE category_id = {$categoryId}
            ORDER BY is_primary DESC, image_id ASC
            LIMIT 1
        ");
        $imgRow = $imgRes ? call_mysql_fetch_array($imgRes) : null;

        if ($imgRow) {
            $resolved = trim((string)($imgRow['file_url'] ?? ''));
            if ($resolved === '') {
                $resolved = trim((string)($imgRow['file_name'] ?? ''));
            }
        }
    }

    if ($resolved === '' && $categoryPhoto !== '') {
        $resolved = $categoryPhoto;
    }

    return [
        'url' => $resolved !== '' ? build_upload_url($resolved) : null,
        'thumb' => $resolved !== '' ? build_thumb_url($resolved) : null,
    ];
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

            if ($start_date === '' || $end_date === '') {
                json_response(['success' => false, 'message' => 'Start and end dates are required.'], 422);
            }

            if ($end_date < $start_date) {
                json_response(['success' => false, 'message' => 'End date must be after start date.'], 422);
            }

            $status = derive_session_status($start_date, $end_date);
            if ($status === 'Active') {
                $active = ensure_single_active_session(0);
                if ($active) {
                    json_response([
                        'success' => false,
                        'message' => 'Only one active session is allowed. Active: ' . ($active['series_code'] ?? ('#' . $active['id']))
                    ], 422);
                }
            }

            $year = date('Y');
            $sqlMax = "
                SELECT MAX(CAST(RIGHT(series_code, 3) AS UNSIGNED)) AS maxnum
                FROM csm_audit_sessions
                WHERE series_code LIKE 'CSM-PC-{$year}-%'
            ";
            $resMax = call_mysql_query($sqlMax);
            $max = 0;
            if ($resMax && ($r = call_mysql_fetch_array($resMax))) {
                $max = (int)($r['maxnum'] ?? 0);
            }

            $next = str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
            $series_code = "CSM-PC-{$year}-{$next}";

            $sql = "
                INSERT INTO csm_audit_sessions
                (series_code, audit_name, start_date, end_date, status, created_by, created_at)
                VALUES (
                    '" . _esc($series_code) . "',
                    " . ($audit_name !== '' ? "'" . _esc($audit_name) . "'" : "NULL") . ",
                    '" . _esc($start_date) . "',
                    '" . _esc($end_date) . "',
                    '" . _esc($status) . "',
                    " . (int)$GLOBALS['s_user_id'] . ",
                    NOW()
                )
            ";

            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to create session.'], 500);
            }

            $newId = get_last_insert_id_safe();

            activity_log_new("CSM PHYSICAL CHECK SESSION CREATE", "SUCCESS", array(
                'session_id' => $newId,
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
            $sql = "
                SELECT s.*, u.f_name, u.l_name
                FROM csm_audit_sessions s
                LEFT JOIN users u ON u.user_id = s.created_by
                ORDER BY s.created_at DESC
            ";
            $res = call_mysql_query($sql);
            $rows = [];

            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['status'] = sync_session_status(
                        (int)($row['id'] ?? 0),
                        (string)($row['start_date'] ?? ''),
                        (string)($row['end_date'] ?? ''),
                        (string)($row['status'] ?? 'Pending')
                    ) ?: (string)($row['status'] ?? 'Pending');
                    $row['created_by_name'] = trim(($row['f_name'] ?? '') . ' ' . ($row['l_name'] ?? ''));
                    $rows[] = $row;
                }
            }

            json_response(['success' => true, 'data' => $rows]);
            break;
        }

        case 'list_active_sessions': {
            $today = date('Y-m-d');
            $sql = "
                SELECT id, series_code, audit_name, start_date, end_date
                FROM csm_audit_sessions
                WHERE start_date <= '" . _esc($today) . "'
                  AND end_date >= '" . _esc($today) . "'
                ORDER BY created_at DESC
            ";
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
            json_response([
                'success' => false,
                'message' => 'Manual session status updates are disabled. Status is automatically derived from date range.'
            ], 422);
            break;
        }

        case 'cancel_session': {
            if (!$isAdmin) {
                json_response(['success' => false, 'message' => 'Access denied.'], 403);
            }

            $session_id = _int(_post('session_id'));
            if ($session_id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid session.'], 422);
            }

            $prevRes = call_mysql_query("
                SELECT id, series_code, start_date, end_date, status
                FROM csm_audit_sessions
                WHERE id = {$session_id}
                LIMIT 1
            ");
            $prevRow = $prevRes ? call_mysql_fetch_array($prevRes) : null;
            if (!$prevRow) {
                json_response(['success' => false, 'message' => 'Session not found.'], 404);
            }

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $oldStart = (string)($prevRow['start_date'] ?? '');
            $oldEnd = (string)($prevRow['end_date'] ?? '');

            // Force date window to end before today so status resolves as Closed.
            $newEnd = ($oldEnd === '' || $oldEnd >= $today) ? $yesterday : $oldEnd;
            $newStart = $oldStart;
            if ($newStart === '' || $newStart > $newEnd) {
                $newStart = $newEnd;
            }

            $sql = "
                UPDATE csm_audit_sessions
                SET start_date = '" . _esc($newStart) . "',
                    end_date = '" . _esc($newEnd) . "',
                    status = 'Closed'
                WHERE id = {$session_id}
                LIMIT 1
            ";

            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to cancel session.'], 500);
            }

            activity_log_new("CSM PHYSICAL CHECK SESSION CANCEL", "SUCCESS", array(
                'session_id' => $session_id,
                'series_code' => $prevRow['series_code'] ?? null,
                'old_start_date' => $oldStart,
                'old_end_date' => $oldEnd,
                'new_start_date' => $newStart,
                'new_end_date' => $newEnd
            ));

            json_response(['success' => true, 'message' => 'Session cancelled.']);
            break;
        }

        case 'get_item_by_code': {
            $item_code = _post('item_code');
            if ($item_code === '') {
                $item_code = _post('property_code'); // compatibility fallback
            }

            if ($item_code === '') {
                json_response(['success' => false, 'message' => 'Inventory item code is required.'], 422);
            }

            $sql = "
                SELECT
                    i.*,
                    c.category_id AS category_id_ref,
                    c.item_category_name,
                    c.category_photo
                FROM csm_inventory i
                LEFT JOIN csm_inventory_category c
                    ON c.item_category_code = i.item_category_code
                WHERE i.inventory_system_item_code = '" . _esc($item_code) . "'
                   OR i.qr_verification = '" . _esc($item_code) . "'
                ORDER BY i.inventory_id DESC
                LIMIT 1
            ";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;

            if (!$row) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }

            $img = resolve_csm_item_image($row);
            $row['item_image_url'] = $img['url'];
            $row['item_image_thumb_url'] = $img['thumb'];
            $row['system_stock_label'] = derive_csm_stock_label($row);

            json_response(['success' => true, 'data' => $row]);
            break;
        }

        case 'create_check': {
            $session_id = _int(_post('session_id'));
            $item_code = _post('item_code');
            if ($item_code === '') {
                $item_code = _post('property_code'); // compatibility fallback
            }

            $counted_quantity = _int(_post('counted_quantity'), 0);
            $remarks = _post('remarks');
            $condition = _post('condition');
            $status_at_check = _post('status_at_check');
            $storage_location = _post('storage_location');

            if ($session_id <= 0 || $item_code === '') {
                json_response(['success' => false, 'message' => 'Session and inventory item code are required.'], 422);
            }

            if ($counted_quantity < 0) {
                json_response(['success' => false, 'message' => 'Counted quantity cannot be negative.'], 422);
            }

            $resSession = call_mysql_query("
                SELECT status, start_date, end_date
                FROM csm_audit_sessions
                WHERE id = {$session_id}
                LIMIT 1
            ");
            $session = $resSession ? call_mysql_fetch_array($resSession) : null;

            if (!$session) {
                json_response(['success' => false, 'message' => 'Session not found.'], 404);
            }

            $effectiveStatus = sync_session_status(
                $session_id,
                (string)($session['start_date'] ?? ''),
                (string)($session['end_date'] ?? ''),
                (string)($session['status'] ?? '')
            );
            if ($effectiveStatus !== 'Active') {
                json_response(['success' => false, 'message' => 'Only active sessions allow checking.'], 422);
            }

            $today = date('Y-m-d');
            $start = (string)($session['start_date'] ?? '');
            $end = (string)($session['end_date'] ?? '');
            if ($start !== '' && $end !== '' && ($today < $start || $today > $end)) {
                json_response(['success' => false, 'message' => 'Session is outside its audit date range.'], 422);
            }

            $resItem = call_mysql_query("
                SELECT *
                FROM csm_inventory
                WHERE inventory_system_item_code = '" . _esc($item_code) . "'
                LIMIT 1
            ");
            $item = $resItem ? call_mysql_fetch_array($resItem) : null;

            if (!$item) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }

            $inventory_id = (int)($item['inventory_id'] ?? 0);
            $inventory_system_item_code = $item['inventory_system_item_code'] ?? '';
            $item_category_code = $item['item_category_code'] ?? '';
            $item_description = $item['item_description'] ?? '';
            $acquisition_date = $item['acquisition_date'] ?? null;
            $cost_value = isset($item['cost_value']) ? (float)$item['cost_value'] : 0;
            $source_of_funds = $item['source_of_funds'] ?? '';
            $system_unit_quantity = (int)($item['quantity'] ?? 0);
            $system_current_quantity = (int)($item['current_quantity'] ?? 0);
            $qty_crit_level = (int)($item['qty_crit_level'] ?? 0);
            $system_status = isset($item['status']) ? (int)$item['status'] : 1;
            $variance_quantity = $counted_quantity - $system_current_quantity;
            $checked_by = (int)$GLOBALS['s_user_id'];

            if ($status_at_check === '') {
                $status_at_check = derive_csm_stock_label($item);
            }

            $existing = call_mysql_query("
                SELECT id
                FROM csm_audit_checks
                WHERE session_id = {$session_id}
                  AND inventory_system_item_code = '" . _esc($inventory_system_item_code) . "'
                LIMIT 1
            ");
            $existingRow = $existing ? call_mysql_fetch_array($existing) : null;

            if ($existingRow) {
                $prevRes = call_mysql_query("
                    SELECT *
                    FROM csm_audit_checks
                    WHERE id = " . (int)$existingRow['id'] . "
                    LIMIT 1
                ");
                $prev = $prevRes ? call_mysql_fetch_array($prevRes) : null;

                $sql = "
                    UPDATE csm_audit_checks SET
                        inventory_id = {$inventory_id},
                        item_category_code = '" . _esc($item_category_code) . "',
                        item_description = '" . _esc($item_description) . "',
                        acquisition_date = " . ($acquisition_date ? "'" . _esc($acquisition_date) . "'" : "NULL") . ",
                        cost_value = " . number_format($cost_value, 2, '.', '') . ",
                        source_of_funds = '" . _esc($source_of_funds) . "',
                        system_unit_quantity = {$system_unit_quantity},
                        system_current_quantity = {$system_current_quantity},
                        counted_quantity = {$counted_quantity},
                        variance_quantity = {$variance_quantity},
                        qty_crit_level = {$qty_crit_level},
                        system_status = {$system_status},
                        status_at_check = '" . _esc($status_at_check) . "',
                        `condition` = '" . _esc($condition) . "',
                        storage_location = '" . _esc($storage_location) . "',
                        remarks = '" . _esc($remarks) . "',
                        checked_by = {$checked_by},
                        checked_at = NOW()
                    WHERE id = " . (int)$existingRow['id'] . "
                    LIMIT 1
                ";
            } else {
                $sql = "
                    INSERT INTO csm_audit_checks
                    (
                        session_id,
                        inventory_id,
                        inventory_system_item_code,
                        item_category_code,
                        item_description,
                        acquisition_date,
                        cost_value,
                        source_of_funds,
                        system_unit_quantity,
                        system_current_quantity,
                        counted_quantity,
                        variance_quantity,
                        qty_crit_level,
                        system_status,
                        status_at_check,
                        `condition`,
                        storage_location,
                        remarks,
                        checked_by,
                        checked_at
                    )
                    VALUES
                    (
                        {$session_id},
                        {$inventory_id},
                        '" . _esc($inventory_system_item_code) . "',
                        '" . _esc($item_category_code) . "',
                        '" . _esc($item_description) . "',
                        " . ($acquisition_date ? "'" . _esc($acquisition_date) . "'" : "NULL") . ",
                        " . number_format($cost_value, 2, '.', '') . ",
                        '" . _esc($source_of_funds) . "',
                        {$system_unit_quantity},
                        {$system_current_quantity},
                        {$counted_quantity},
                        {$variance_quantity},
                        {$qty_crit_level},
                        {$system_status},
                        '" . _esc($status_at_check) . "',
                        '" . _esc($condition) . "',
                        '" . _esc($storage_location) . "',
                        '" . _esc($remarks) . "',
                        {$checked_by},
                        NOW()
                    )
                ";
            }

            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to save check.'], 500);
            }

            activity_log_new($existingRow ? "CSM PHYSICAL CHECK UPDATE" : "CSM PHYSICAL CHECK CREATE", "SUCCESS", array(
                'session_id' => $session_id,
                'inventory_id' => $inventory_id,
                'inventory_system_item_code' => $inventory_system_item_code,
                'item_category_code' => $item_category_code,
                'item_description' => $item_description,
                'system_unit_quantity' => $system_unit_quantity,
                'system_current_quantity' => $system_current_quantity,
                'counted_quantity' => $counted_quantity,
                'variance_quantity' => $variance_quantity,
                'qty_crit_level' => $qty_crit_level,
                'status_at_check' => $status_at_check,
                'condition' => $condition,
                'storage_location' => $storage_location,
                'remarks' => $remarks,
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
                $where[] = "(
                    c.inventory_system_item_code LIKE '{$searchEsc}'
                    OR c.item_description LIKE '{$searchEsc}'
                    OR c.item_category_code LIKE '{$searchEsc}'
                    OR c.status_at_check LIKE '{$searchEsc}'
                    OR c.storage_location LIKE '{$searchEsc}'
                )";
            }

            $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

            $sql = "
                SELECT c.*, s.series_code, u.f_name, u.l_name
                FROM csm_audit_checks c
                LEFT JOIN csm_audit_sessions s ON s.id = c.session_id
                LEFT JOIN users u ON u.user_id = c.checked_by
                {$whereSql}
                ORDER BY c.checked_at DESC
            ";
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
