<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

if (!(role_has("USER") || role_has("USERS"))) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'message' => 'Access denied.'));
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
    global $db_connect, $conn;
    $link = null;
    if (isset($db_connect) && $db_connect instanceof mysqli) {
        $link = $db_connect;
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $link = $conn;
    }
    return $link ? mysqli_real_escape_string($link, (string)$v) : addslashes((string)$v);
}

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function fr_table_exists($table) {
    $res = call_mysql_query("SHOW TABLES LIKE '" . _esc($table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function fr_column_exists($table, $column) {
    $res = call_mysql_query("SHOW COLUMNS FROM `" . _esc($table) . "` LIKE '" . _esc($column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function fr_required_tables_ready() {
    return fr_table_exists('facility_records_facilities')
        && fr_table_exists('facility_records_units')
        && fr_table_exists('facility_records_assignments')
        && fr_table_exists('facility_records_history');
}

function fr_unit_manager_column() {
    if (fr_column_exists('facility_records_units', 'facility_unit_manager_user_id')) return 'facility_unit_manager_user_id';
    if (fr_column_exists('facility_records_units', 'accountable_user_id')) return 'accountable_user_id';
    return '';
}

function user_managed_unit_ids($userId) {
    $userId = (int)$userId;
    if ($userId <= 0) return array();
    $col = fr_unit_manager_column();
    if ($col === '') return array();
    $sql = "SELECT unit_id FROM facility_records_units WHERE {$col} = {$userId}";
    $res = call_mysql_query($sql);
    $ids = array();
    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            $ids[] = (int)$row['unit_id'];
        }
    }
    return $ids;
}

function user_managed_facility_ids($userId) {
    $userId = (int)$userId;
    if ($userId <= 0) return array();
    $col = fr_unit_manager_column();
    if ($col === '') return array();
    $sql = "SELECT DISTINCT facility_id FROM facility_records_units WHERE {$col} = {$userId}";
    $res = call_mysql_query($sql);
    $ids = array();
    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            $ids[] = (int)$row['facility_id'];
        }
    }
    return $ids;
}

function fr_resolve_row_images(&$row) {
    $row['category_photo_url'] = null;
    $row['category_photo_thumb_url'] = null;
    $row['item_category_name'] = null;

    if ($row['module_type'] === 'AST') {
        $row['item_category_name'] = $row['ast_category_name'];
        if (!empty($row['ast_category_photo'])) {
            $row['category_photo_url'] = BASE_URL . 'upload/category/' . $row['ast_category_photo'];
            $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($row['ast_category_photo']) . '&s=100';
        }
    } elseif ($row['module_type'] === 'CSM') {
        $row['item_category_name'] = $row['csm_category_name'];
        $img = $row['csm_category_img'];
        if (!empty($img)) {
            if (strpos($img, 'upload/') === 0 || strpos($img, '/') !== false) {
                $row['category_photo_url'] = BASE_URL . $img;
                $fname = basename($img);
                $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($fname) . '&s=100';
            } elseif (ctype_digit($img)) {
                $imgRes = call_mysql_query("SELECT file_name, file_url FROM csm_inventory_category_images WHERE image_id = " . (int)$img . " LIMIT 1");
                if ($imgRes && $imgRow = call_mysql_fetch_array($imgRes)) {
                    $furl = !empty($imgRow['file_url']) ? $imgRow['file_url'] : 'upload/category/' . $imgRow['file_name'];
                    $row['category_photo_url'] = BASE_URL . $furl;
                    $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($imgRow['file_name']) . '&s=100';
                }
            } else {
                $row['category_photo_url'] = BASE_URL . 'upload/category/' . $img;
                $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($img) . '&s=100';
            }
        }
    }

    unset($row['ast_category_photo'], $row['ast_category_name'], $row['csm_category_img'], $row['csm_category_name']);
}

function fr_assignments_query($whereClause, $hasManagedBy) {
    return "SELECT
                a.assignment_id,
                a.module_type,
                a.item_code,
                a.item_description,
                a.qty,
                a.unit,
                a.status,
                a.issued_at,
                a.returned_at,
                a.remarks,
                CONCAT(COALESCE(u1.f_name,''), ' ', COALESCE(u1.l_name,'')) AS issued_to_name,
                CONCAT(COALESCE(u2.f_name,''), ' ', COALESCE(u2.l_name,'')) AS accountable_name,
                " . ($hasManagedBy ? "CONCAT(COALESCE(u3.f_name,''), ' ', COALESCE(u3.l_name,''))" : "''") . " AS managed_by_name,
                f.facility_code,
                f.facility_name,
                u.unit_code,
                u.unit_name,
                ast_cat.category_photo AS ast_category_photo,
                ast_cat.item_category_name AS ast_category_name,
                csm_inv.item_category_img AS csm_category_img,
                csm_cat.item_category_name AS csm_category_name
            FROM facility_records_assignments a
            LEFT JOIN users u1 ON u1.user_id = a.issued_to_user_id
            LEFT JOIN users u2 ON u2.user_id = a.accountable_user_id
            " . ($hasManagedBy ? "LEFT JOIN users u3 ON u3.user_id = a.managed_by_user_id" : "") . "
            LEFT JOIN facility_records_facilities f ON f.facility_id = a.facility_id
            LEFT JOIN facility_records_units u ON u.unit_id = a.unit_id
            LEFT JOIN ast_inventory ast_inv ON a.module_type = 'AST' AND a.item_code = ast_inv.property_code
            LEFT JOIN ast_inventory_category ast_cat ON ast_inv.category_id = ast_cat.category_id
            LEFT JOIN csm_inventory csm_inv ON a.module_type = 'CSM' AND a.item_code = csm_inv.inventory_system_item_code
            LEFT JOIN csm_inventory_category csm_cat ON csm_inv.item_category_code = csm_cat.item_category_code
            WHERE {$whereClause}
            ORDER BY a.issued_at DESC, a.assignment_id DESC";
}

function fr_fetch_assignments($whereClause) {
    $hasManagedBy = fr_column_exists('facility_records_assignments', 'managed_by_user_id');
    $sql = fr_assignments_query($whereClause, $hasManagedBy);
    $res = call_mysql_query($sql);
    $rows = array();
    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            fr_resolve_row_images($row);
            $rows[] = $row;
        }
    }
    return $rows;
}

$action = _post('action', '');
if ($action === '') {
    json_response(array('success' => false, 'message' => 'Missing action.'), 400);
}

if (!fr_required_tables_ready()) {
    json_response(array('success' => false, 'message' => 'Facility tables are missing. Run migration: db/migrations/2026-03-09_facility_inventory_records.sql'), 500);
}

try {
    switch ($action) {
        case 'list_managed_units':
            global $s_user_id;
            $uid = (int)$s_user_id;
            if ($uid <= 0) json_response(array('success' => false, 'message' => 'Invalid user.'), 403);
            $col = fr_unit_manager_column();
            if ($col === '') json_response(array('success' => true, 'data' => array()));
            $mgrSelect = "CONCAT(COALESCE(mgr.f_name,''), ' ', COALESCE(mgr.l_name,'')) AS unit_manager_name,";
            $sql = "SELECT
                        u.unit_id,
                        u.facility_id,
                        u.unit_type,
                        u.unit_code,
                        u.unit_name,
                        u.status,
                        f.facility_code,
                        f.facility_name,
                        {$mgrSelect}
                        (SELECT COUNT(*) FROM facility_records_assignments a WHERE a.unit_id = u.unit_id AND a.status IN ('ACTIVE','REPORTED','RETURN_REQUESTED')) AS active_item_count
                    FROM facility_records_units u
                    INNER JOIN facility_records_facilities f ON f.facility_id = u.facility_id
                    LEFT JOIN users mgr ON mgr.user_id = u.{$col}
                    WHERE u.{$col} = {$uid}
                    ORDER BY f.facility_name ASC, u.unit_name ASC";
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'list_unit_assignments':
            global $s_user_id;
            $uid = (int)$s_user_id;
            $unit_id = _int(_post('unit_id'), 0);
            if ($uid <= 0) json_response(array('success' => false, 'message' => 'Invalid user.'), 403);
            if ($unit_id <= 0) json_response(array('success' => false, 'message' => 'Unit is required.'), 422);
            $managed = user_managed_unit_ids($uid);
            if (!in_array($unit_id, $managed, true)) {
                json_response(array('success' => false, 'message' => 'You are not allowed to view this unit.'), 403);
            }
            $rows = fr_fetch_assignments("a.unit_id = {$unit_id}");
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'list_facility_assignments':
            global $s_user_id;
            $uid = (int)$s_user_id;
            $facility_id = _int(_post('facility_id'), 0);
            if ($uid <= 0) json_response(array('success' => false, 'message' => 'Invalid user.'), 403);
            if ($facility_id <= 0) json_response(array('success' => false, 'message' => 'Facility is required.'), 422);
            $managedFacilities = user_managed_facility_ids($uid);
            if (!in_array($facility_id, $managedFacilities, true)) {
                json_response(array('success' => false, 'message' => 'You are not allowed to view this facility.'), 403);
            }
            // Get all unit_ids in this facility that the user manages
            $managed = user_managed_unit_ids($uid);
            $facilityUnitIds = array();
            foreach ($managed as $mid) {
                $chk = call_mysql_query("SELECT unit_id FROM facility_records_units WHERE unit_id = {$mid} AND facility_id = {$facility_id} LIMIT 1");
                if ($chk && call_mysql_fetch_array($chk)) {
                    $facilityUnitIds[] = $mid;
                }
            }
            if (empty($facilityUnitIds)) {
                json_response(array('success' => true, 'data' => array()));
            }
            $inList = implode(',', $facilityUnitIds);
            $rows = fr_fetch_assignments("a.unit_id IN ({$inList})");
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'list_my_assignments':
            global $s_user_id;
            $uid = (int)$s_user_id;
            if ($uid <= 0) json_response(array('success' => false, 'message' => 'Invalid user.'), 403);
            $status = strtoupper(_post('status', ''));
            $allowedStatus = array('ACTIVE', 'REPORTED', 'RETURN_REQUESTED', 'RETURNED');
            $statusWhere = '';
            if ($status !== '' && in_array($status, $allowedStatus, true)) {
                $statusWhere = " AND a.status = '" . _esc($status) . "'";
            }
            $hasManagedBy = fr_column_exists('facility_records_assignments', 'managed_by_user_id');
            $where = "WHERE (a.issued_to_user_id = {$uid} OR a.accountable_user_id = {$uid}";
            if ($hasManagedBy) {
                $where .= " OR a.managed_by_user_id = {$uid}";
            }
            $where .= ")" . $statusWhere;
            $sql = "SELECT
                        a.assignment_id,
                        a.module_type,
                        a.item_code,
                        a.item_description,
                        a.qty,
                        a.unit,
                        a.status,
                        a.issued_at,
                        a.returned_at,
                        a.remarks,
                        f.facility_code,
                        f.facility_name,
                        u.unit_code,
                        u.unit_name,
                        CONCAT(COALESCE(u1.f_name,''), ' ', COALESCE(u1.l_name,'')) AS issued_to_name,
                        CONCAT(COALESCE(u2.f_name,''), ' ', COALESCE(u2.l_name,'')) AS accountable_name,
                        " . ($hasManagedBy ? "CONCAT(COALESCE(u3.f_name,''), ' ', COALESCE(u3.l_name,''))" : "''") . " AS managed_by_name
                    FROM facility_records_assignments a
                    LEFT JOIN facility_records_facilities f ON f.facility_id = a.facility_id
                    LEFT JOIN facility_records_units u ON u.unit_id = a.unit_id
                    LEFT JOIN users u1 ON u1.user_id = a.issued_to_user_id
                    LEFT JOIN users u2 ON u2.user_id = a.accountable_user_id
                    " . ($hasManagedBy ? "LEFT JOIN users u3 ON u3.user_id = a.managed_by_user_id" : "") . "
                    {$where}
                    ORDER BY a.issued_at DESC, a.assignment_id DESC";
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'update_status':
            global $s_user_id;
            $uid = (int)$s_user_id;
            $assignment_id = _int(_post('assignment_id'), 0);
            $new_status = strtoupper(_post('status'));
            $remarks = _post('remarks');
            $allowed = array('REPORTED', 'RETURN_REQUESTED');
            if ($uid <= 0) json_response(array('success' => false, 'message' => 'Invalid user.'), 403);
            if ($assignment_id <= 0 || !in_array($new_status, $allowed, true)) {
                json_response(array('success' => false, 'message' => 'Invalid status payload.'), 422);
            }
            $hasManagedBy = fr_column_exists('facility_records_assignments', 'managed_by_user_id');
            $managerCol = fr_unit_manager_column();
            $mgrSelect = $managerCol !== '' ? "u.{$managerCol} AS unit_manager_id," : "NULL AS unit_manager_id,";
            $managedSelect = $hasManagedBy ? "a.managed_by_user_id," : "NULL AS managed_by_user_id,";
            $sql = "SELECT a.assignment_id, a.status, a.unit_id, a.issued_to_user_id, a.accountable_user_id, {$managedSelect} {$mgrSelect} a.remarks
                    FROM facility_records_assignments a
                    LEFT JOIN facility_records_units u ON u.unit_id = a.unit_id
                    WHERE a.assignment_id = {$assignment_id}
                    LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) json_response(array('success' => false, 'message' => 'Assignment not found.'), 404);
            $oldStatus = strtoupper((string)$row['status']);
            $unitManagerId = (int)($row['unit_manager_id'] ?? 0);
            $managedById = (int)($row['managed_by_user_id'] ?? 0);
            $issuedToId = (int)($row['issued_to_user_id'] ?? 0);
            $accountableId = (int)($row['accountable_user_id'] ?? 0);
            $allowedAccess = in_array($uid, array($unitManagerId, $managedById, $issuedToId, $accountableId), true);
            if (!$allowedAccess) {
                json_response(array('success' => false, 'message' => 'You are not allowed to update this assignment.'), 403);
            }
            if ($oldStatus === 'RETURNED') {
                json_response(array('success' => false, 'message' => 'Item is already marked as returned.'), 422);
            }
            $ok = call_mysql_query("UPDATE facility_records_assignments
                                    SET status = '" . _esc($new_status) . "',
                                        remarks = " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "remarks") . ",
                                        updated_by = {$uid}
                                    WHERE assignment_id = {$assignment_id}
                                    LIMIT 1");
            if (!$ok) json_response(array('success' => false, 'message' => 'Failed to update status.'), 500);
            call_mysql_query("INSERT INTO facility_records_history (assignment_id, action, old_status, new_status, remarks, actor_user_id)
                              VALUES ({$assignment_id}, 'STATUS_UPDATE', '" . _esc($oldStatus) . "', '" . _esc($new_status) . "', " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", {$uid})");
            json_response(array('success' => true, 'message' => 'Status updated.'));
            break;

        default:
            json_response(array('success' => false, 'message' => 'Unknown action.'), 400);
    }
} catch (Throwable $e) {
    json_response(array('success' => false, 'message' => 'Server error occurred.'), 500);
}

