<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$staffAccess = (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("PO", "AST", "CSM"));
if (!(role_has("ADMIN") || $staffAccess)) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'message' => 'Access denied.'));
    exit();
}

function _post($k, $default = '')
{
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default;
}

function _int($v, $default = 0)
{
    if ($v === '' || $v === null) return $default;
    return (int)$v;
}

function _float($v, $default = 0.0)
{
    if ($v === '' || $v === null) return $default;
    return (float)$v;
}

function _esc($v)
{
    global $db_connect;
    return mysqli_real_escape_string($db_connect, (string)$v);
}

function json_response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function fr_table_exists($table)
{
    $res = call_mysql_query("SHOW TABLES LIKE '" . _esc($table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function fr_required_tables_ready()
{
    return fr_table_exists('facility_records_facilities')
        && fr_table_exists('facility_records_units')
        && fr_table_exists('facility_records_assignments')
        && fr_table_exists('facility_records_history');
}

function fr_column_exists($table, $column)
{
    $res = call_mysql_query("SHOW COLUMNS FROM `" . _esc($table) . "` LIKE '" . _esc($column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function fr_resolve_row_images(&$row) {
    $row['category_photo_url'] = null;
    $row['category_photo_thumb_url'] = null;
    $row['item_category_name'] = null;

    if ($row['module_type'] === 'AST') {
        $row['item_category_name'] = $row['ast_category_name'] ?? null;
        if (!empty($row['ast_category_photo'])) {
            $row['category_photo_url'] = BASE_URL . 'upload/category/' . $row['ast_category_photo'];
            $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($row['ast_category_photo']) . '&s=100';
        }
    } elseif ($row['module_type'] === 'CSM') {
        $row['item_category_name'] = $row['csm_category_name'] ?? null;
        $img = $row['csm_category_img'] ?? '';
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

function fr_active_user_where_clause()
{
    if (!fr_column_exists('users', 'status')) {
        return "1=1";
    }
    $res1 = call_mysql_query("SELECT COUNT(*) AS cnt FROM users WHERE status = 1");
    $row1 = $res1 ? call_mysql_fetch_array($res1) : null;
    $cnt1 = (int)($row1['cnt'] ?? 0);
    if ($cnt1 > 0) return "u.status = 1";
    $res0 = call_mysql_query("SELECT COUNT(*) AS cnt FROM users WHERE status = 0");
    $row0 = $res0 ? call_mysql_fetch_array($res0) : null;
    $cnt0 = (int)($row0['cnt'] ?? 0);
    if ($cnt0 > 0) return "u.status = 0";
    return "1=1";
}

$action = _post('action');
if ($action === '') {
    json_response(array('success' => false, 'message' => 'Missing action.'), 400);
}

if (!fr_required_tables_ready()) {
    json_response(array('success' => false, 'message' => 'Facility tables are missing. Run migration: db/migrations/2026-03-09_facility_inventory_records.sql'), 500);
}

try {
    switch ($action) {
        case 'locate_assignment':
            $item_code = trim((string)_post('item_code'));
            if ($item_code === '') {
                json_response(array('success' => false, 'message' => 'Item code is required.'), 422);
            }
            $module_type = '';
            $upper = strtoupper($item_code);
            if (strpos($upper, 'AST-') === 0) $module_type = 'AST';
            if (strpos($upper, 'CSM-') === 0) $module_type = 'CSM';
            $whereModule = $module_type !== '' ? "AND a.module_type = '" . _esc($module_type) . "'" : "";

            $sql = "SELECT
                        a.assignment_id,
                        a.module_type,
                        a.item_code,
                        a.status,
                        a.facility_id,
                        a.unit_id,
                        f.facility_name,
                        f.facility_code,
                        u.unit_name,
                        u.unit_code
                    FROM facility_records_assignments a
                    LEFT JOIN facility_records_facilities f ON f.facility_id = a.facility_id
                    LEFT JOIN facility_records_units u ON u.unit_id = a.unit_id
                    WHERE a.item_code = '" . _esc($item_code) . "'
                      {$whereModule}
                      AND a.status <> 'RETURNED'
                    ORDER BY a.issued_at DESC, a.assignment_id DESC
                    LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) {
                json_response(array('success' => false, 'message' => 'No active assignment found for this item.'));
            }
            json_response(array('success' => true, 'data' => $row));
            break;
        case 'list_facilities':
            $sql = "SELECT
                        f.facility_id,
                        f.facility_code,
                        f.facility_name,
                        f.facility_floor,
                        f.status,
                        (
                            SELECT COUNT(*)
                            FROM facility_records_units u
                            WHERE u.facility_id = f.facility_id AND u.status = 1
                        ) AS unit_count,
                        (
                            SELECT COUNT(*)
                            FROM facility_records_assignments a
                            WHERE a.facility_id = f.facility_id AND a.status = 'ACTIVE'
                        ) AS active_item_count
                    FROM facility_records_facilities f
                    ORDER BY f.facility_name ASC";
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'save_facility':
            global $s_user_id;
            $facility_id = _int(_post('facility_id'), 0);
            $facility_code = strtoupper(_post('facility_code'));
            $facility_name = _post('facility_name');
            $floors_raw = _post('facility_floor');
            $floors_list = array();
            if (is_array($floors_raw)) {
                $floors_list = $floors_raw;
            } elseif ($floors_raw !== '') {
                $decoded = json_decode((string)$floors_raw, true);
                if (is_array($decoded)) $floors_list = $decoded;
            }
            $floors_clean = array();
            foreach ($floors_list as $f) {
                $name = trim((string)$f);
                if ($name === '') continue;
                if (!in_array($name, $floors_clean, true)) $floors_clean[] = $name;
            }
            $floors_json = json_encode(array('floors' => $floors_clean), JSON_UNESCAPED_UNICODE);
            $floors_sql = "'" . _esc($floors_json) . "'";
            if ($facility_code === '' || $facility_name === '') {
                json_response(array('success' => false, 'message' => 'Facility code and name are required.'), 422);
            }

            if ($facility_id > 0) {
                $dup = call_mysql_query("SELECT facility_id FROM facility_records_facilities WHERE facility_code = '" . _esc($facility_code) . "' AND facility_id <> {$facility_id} LIMIT 1");
                if ($dup && mysqli_num_rows($dup) > 0) {
                    json_response(array('success' => false, 'message' => 'Facility code already exists.'), 409);
                }
                $ok = call_mysql_query("UPDATE facility_records_facilities
                                        SET facility_code = '" . _esc($facility_code) . "',
                                            facility_name = '" . _esc($facility_name) . "',
                                            facility_floor = {$floors_sql},
                                            updated_by = " . (int)$s_user_id . "
                                        WHERE facility_id = {$facility_id}
                                        LIMIT 1");
                if (!$ok) json_response(array('success' => false, 'message' => 'Failed to update facility.'), 500);
                activity_log_new("FACILITY UPDATE", "SUCCESS", array('facility_id' => $facility_id, 'facility_code' => $facility_code, 'facility_name' => $facility_name, 'floors' => $floors_clean));
                json_response(array('success' => true, 'message' => 'Facility updated.'));
            }

            $dup = call_mysql_query("SELECT facility_id FROM facility_records_facilities WHERE facility_code = '" . _esc($facility_code) . "' LIMIT 1");
            if ($dup && mysqli_num_rows($dup) > 0) {
                json_response(array('success' => false, 'message' => 'Facility code already exists.'), 409);
            }
            $ok = call_mysql_query("INSERT INTO facility_records_facilities (facility_code, facility_name, facility_floor, created_by, updated_by)
                                    VALUES ('" . _esc($facility_code) . "', '" . _esc($facility_name) . "', {$floors_sql}, " . (int)$s_user_id . ", " . (int)$s_user_id . ")");
            if (!$ok) json_response(array('success' => false, 'message' => 'Failed to create facility.'), 500);
            activity_log_new("FACILITY CREATE", "SUCCESS", array('facility_code' => $facility_code, 'facility_name' => $facility_name, 'floors' => $floors_clean));
            json_response(array('success' => true, 'message' => 'Facility created.'));
            break;

        case 'list_units':
            $facility_id = _int(_post('facility_id'), 0);
            if ($facility_id <= 0) json_response(array('success' => false, 'message' => 'Facility is required.'), 422);
            $hasUnitManager = fr_column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = fr_column_exists('facility_records_units', 'accountable_user_id');
            $unitManagerCol = $hasUnitManager ? 'facility_unit_manager_user_id' : ($hasUnitManagerLegacy ? 'accountable_user_id' : '');
            $hasUnitManagersTable = fr_table_exists('facility_records_unit_managers');

            if ($hasUnitManagersTable) {
                $sql = "SELECT
                            u.unit_id,
                            u.facility_id,
                            u.unit_type,
                            u.unit_code,
                            u.unit_name,
                            u.floor_label,
                            u.status,
                            MIN(um.user_id) AS facility_unit_manager_user_id,
                            GROUP_CONCAT(DISTINCT um.user_id ORDER BY ua.l_name SEPARATOR ',') AS facility_unit_manager_user_ids,
                            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) ORDER BY ua.l_name SEPARATOR '||') AS unit_manager_names,
                            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) ORDER BY ua.l_name SEPARATOR ', ') AS unit_manager_name,
                            (
                                SELECT COUNT(*)
                                FROM facility_records_assignments a
                                WHERE a.unit_id = u.unit_id AND a.status = 'ACTIVE'
                            ) AS active_item_count
                        FROM facility_records_units u
                        LEFT JOIN facility_records_unit_managers um ON um.unit_id = u.unit_id
                        LEFT JOIN users ua ON ua.user_id = um.user_id
                        WHERE u.facility_id = {$facility_id}
                        GROUP BY u.unit_id
                        ORDER BY u.unit_name ASC";
            } else {
                $sql = "SELECT
                            u.unit_id,
                            u.facility_id,
                            u.unit_type,
                            u.unit_code,
                            u.unit_name,
                            u.floor_label,
                            u.status,
                            " . ($unitManagerCol !== '' ? "u.{$unitManagerCol} AS facility_unit_manager_user_id," : "NULL AS facility_unit_manager_user_id,") . "
                            CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) AS unit_manager_name,
                            NULL AS facility_unit_manager_user_ids,
                            NULL AS unit_manager_names,
                            (
                                SELECT COUNT(*)
                                FROM facility_records_assignments a
                                WHERE a.unit_id = u.unit_id AND a.status = 'ACTIVE'
                            ) AS active_item_count
                        FROM facility_records_units u
                        LEFT JOIN users ua ON ua.user_id = " . ($unitManagerCol !== '' ? "u.{$unitManagerCol}" : "0") . "
                        WHERE u.facility_id = {$facility_id}
                        ORDER BY u.unit_name ASC";
            }
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'save_unit':
            global $s_user_id;
            $unit_id = _int(_post('unit_id'), 0);
            $facility_id = _int(_post('facility_id'), 0);
            $unit_type = strtoupper(_post('unit_type'));
            $unit_code = strtoupper(_post('unit_code'));
            $unit_name = _post('unit_name');
            $floor_label_raw = trim((string)_post('floor_label'));
            $floor_label = $floor_label_raw !== '' ? $floor_label_raw : null;
            $floor_sql = $floor_label !== null ? "'" . _esc($floor_label) . "'" : "NULL";
            $managerIdsRaw = $_POST['facility_unit_manager_user_ids'] ?? array();
            if (!is_array($managerIdsRaw)) {
                $managerIdsRaw = array_filter(array_map('trim', explode(',', (string)$managerIdsRaw)));
            }
            $managerIds = array_values(array_unique(array_filter(array_map(function($id){
                return _int($id, 0);
            }, $managerIdsRaw))));
            $facility_unit_manager_user_id = count($managerIds) > 0 ? (int)$managerIds[0] : 0;
            $allowed = array('ROOM', 'OFFICE', 'LABORATORY', 'OTHER');
            if ($facility_id <= 0 || $unit_code === '' || $unit_name === '') {
                json_response(array('success' => false, 'message' => 'Unit code, name, and facility are required.'), 422);
            }
            if (!in_array($unit_type, $allowed, true)) {
                $unit_type = 'ROOM';
            }
            $hasUnitManager = fr_column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = fr_column_exists('facility_records_units', 'accountable_user_id');
            $unitManagerCol = $hasUnitManager ? 'facility_unit_manager_user_id' : ($hasUnitManagerLegacy ? 'accountable_user_id' : '');
            $hasUnitManagersTable = fr_table_exists('facility_records_unit_managers');

            if ($unit_id > 0) {
                $dup = call_mysql_query("SELECT unit_id FROM facility_records_units
                                         WHERE facility_id = {$facility_id}
                                         AND unit_code = '" . _esc($unit_code) . "'
                                         AND unit_id <> {$unit_id}
                                         LIMIT 1");
                if ($dup && mysqli_num_rows($dup) > 0) {
                    json_response(array('success' => false, 'message' => 'Unit code already exists for this facility.'), 409);
                }
                $ok = call_mysql_query("UPDATE facility_records_units
                                        SET unit_type = '" . _esc($unit_type) . "',
                                            unit_code = '" . _esc($unit_code) . "',
                                            unit_name = '" . _esc($unit_name) . "',
                                            floor_label = {$floor_sql},
                                            " . ($unitManagerCol !== '' ? "{$unitManagerCol} = " . ($facility_unit_manager_user_id > 0 ? $facility_unit_manager_user_id : "NULL") . "," : "") . "
                                            updated_by = " . (int)$s_user_id . "
                                        WHERE unit_id = {$unit_id}
                                        LIMIT 1");
                if (!$ok) json_response(array('success' => false, 'message' => 'Failed to update unit.'), 500);
                if ($hasUnitManagersTable) {
                    call_mysql_query("DELETE FROM facility_records_unit_managers WHERE unit_id = {$unit_id}");
                    foreach ($managerIds as $mid) {
                        call_mysql_query("INSERT INTO facility_records_unit_managers (unit_id, user_id) VALUES ({$unit_id}, " . (int)$mid . ")");
                    }
                }
                activity_log_new("FACILITY UNIT UPDATE", "SUCCESS", array('unit_id' => $unit_id, 'facility_id' => $facility_id, 'unit_code' => $unit_code, 'unit_name' => $unit_name, 'floor_label' => $floor_label, 'facility_unit_manager_user_ids' => $managerIds));
                json_response(array('success' => true, 'message' => 'Unit updated.'));
            }

            $dup = call_mysql_query("SELECT unit_id FROM facility_records_units
                                     WHERE facility_id = {$facility_id}
                                     AND unit_code = '" . _esc($unit_code) . "'
                                     LIMIT 1");
            if ($dup && mysqli_num_rows($dup) > 0) {
                json_response(array('success' => false, 'message' => 'Unit code already exists for this facility.'), 409);
            }

            $insertCols = "facility_id, unit_type, unit_code, unit_name, floor_label";
            $insertVals = "{$facility_id}, '" . _esc($unit_type) . "', '" . _esc($unit_code) . "', '" . _esc($unit_name) . "', {$floor_sql}";
            if ($unitManagerCol !== '') {
                $insertCols .= ", {$unitManagerCol}";
                $insertVals .= ", " . ($facility_unit_manager_user_id > 0 ? $facility_unit_manager_user_id : "NULL");
            }
            $insertCols .= ", created_by, updated_by";
            $insertVals .= ", " . (int)$s_user_id . ", " . (int)$s_user_id;
            $ok = call_mysql_query("INSERT INTO facility_records_units ({$insertCols}) VALUES ({$insertVals})");
            if (!$ok) json_response(array('success' => false, 'message' => 'Failed to create unit.'), 500);
            $newUnitId = (int)mysqli_insert_id($db_connect);
            if ($hasUnitManagersTable && $newUnitId > 0) {
                foreach ($managerIds as $mid) {
                    call_mysql_query("INSERT INTO facility_records_unit_managers (unit_id, user_id) VALUES ({$newUnitId}, " . (int)$mid . ")");
                }
            }
            activity_log_new("FACILITY UNIT CREATE", "SUCCESS", array('facility_id' => $facility_id, 'unit_code' => $unit_code, 'unit_name' => $unit_name, 'floor_label' => $floor_label, 'facility_unit_manager_user_ids' => $managerIds));
            json_response(array('success' => true, 'message' => 'Unit created.'));
            break;

        case 'list_users':
            $search = _post('search');
            $permanent_only = _int(_post('permanent_only'), 0) === 1;
            $limit = _int(_post('limit'), 500);
            if ($limit <= 0) $limit = 500;
            if ($limit > 500) $limit = 500;
            $hasEmail = fr_column_exists('users', 'email');
            $hasEmailAddress = fr_column_exists('users', 'email_address');
            $hasRoleId = fr_column_exists('users', 'role_id');
            $hasUserRole = fr_column_exists('users', 'user_role');
            $where = "WHERE " . fr_active_user_where_clause();
            $hasEmploymentStatusTable = fr_table_exists('employment_status');
            $hasUserEmploymentStatusId = fr_column_exists('users', 'employment_status_id');
            $emailExpr = $hasEmail ? "u.email" : ($hasEmailAddress ? "u.email_address" : "''");
            $roleExpr = $hasRoleId ? "u.role_id" : "NULL";
            $userRoleExpr = $hasUserRole ? "u.user_role" : "''";
            if ($search !== '') {
                $s = _esc('%' . $search . '%');
                $where .= " AND (u.f_name LIKE '{$s}' OR u.l_name LIKE '{$s}' OR {$emailExpr} LIKE '{$s}' OR u.username LIKE '{$s}')";
            }
            if ($hasEmploymentStatusTable && $hasUserEmploymentStatusId) {
                if ($permanent_only) {
                    $where .= " AND LOWER(COALESCE(es.status_code, '')) = 'permanent'";
                }
                $sql = "SELECT
                            u.user_id,
                            {$emailExpr} AS email,
                            u.username,
                            {$roleExpr} AS role_id,
                            {$userRoleExpr} AS user_role,
                            u.position,
                            es.status_code AS employment_status_code,
                            es.status_name AS employment_status_name,
                            CONCAT(COALESCE(u.l_name,''), ', ', COALESCE(u.f_name,''), IF(COALESCE(u.m_name,'') <> '', CONCAT(' ', LEFT(u.m_name,1), '.'), '')) AS full_name
                        FROM users u
                        LEFT JOIN employment_status es ON es.employment_status_id = u.employment_status_id
                        {$where}
                        ORDER BY u.l_name ASC, u.f_name ASC
                        LIMIT " . (int)$limit;
            } else {
                if ($permanent_only) {
                    // If employment-status schema is unavailable, do not hard-fail UI.
                    json_response(array('success' => true, 'data' => array()));
                }
                $sql = "SELECT
                            u.user_id,
                            {$emailExpr} AS email,
                            u.username,
                            {$roleExpr} AS role_id,
                            {$userRoleExpr} AS user_role,
                            u.position,
                            '' AS employment_status_code,
                            '' AS employment_status_name,
                            CONCAT(COALESCE(u.l_name,''), ', ', COALESCE(u.f_name,''), IF(COALESCE(u.m_name,'') <> '', CONCAT(' ', LEFT(u.m_name,1), '.'), '')) AS full_name
                        FROM users u
                        {$where}
                        ORDER BY u.l_name ASC, u.f_name ASC
                        LIMIT " . (int)$limit;
            }
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'list_available_items':
            $module_type = strtoupper(_post('module_type', 'AST'));
            $search = _post('search');
            $rows = array();
            $search = trim(preg_replace('/\s+/', ' ', (string)$search));

            if ($module_type === 'CSM') {
                $where = "WHERE c.status = 'available' AND c.current_unit_quantity > 0";
                if ($search !== '') {
                    $s = _esc('%' . $search . '%');
                    $where .= " AND (
                        c.inventory_system_item_code LIKE '{$s}'
                        OR c.item_description LIKE '{$s}'
                        OR CONCAT_WS(' ', c.inventory_system_item_code, c.item_description) LIKE '{$s}'
                    )";
                }
                $sql = "SELECT
                            c.inventory_id AS source_item_id,
                            c.inventory_system_item_code AS item_code,
                            c.item_description,
                            c.current_unit_quantity AS available_qty,
                            '' AS unit
                        FROM csm_inventory c
                        {$where}
                        ORDER BY c.inventory_system_item_code ASC";
            } else {
                $module_type = 'AST';
                $where = "WHERE a.is_available = 1";
                if ($search !== '') {
                    $s = _esc('%' . $search . '%');
                    $where .= " AND (
                        a.property_code LIKE '{$s}'
                        OR a.item_description LIKE '{$s}'
                        OR a.serial_number LIKE '{$s}'
                        OR CONCAT_WS(' ', a.property_code, a.item_description, a.serial_number) LIKE '{$s}'
                    )";
                }
                $sql = "SELECT
                            a.item_id AS source_item_id,
                            a.property_code AS item_code,
                            a.item_description,
                            a.unit
                        FROM ast_inventory a
                        {$where}
                        ORDER BY a.property_code ASC";
            }
            $res = call_mysql_query($sql);
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['module_type'] = $module_type;
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'diagnose_item_search':
            $module_type = strtoupper(_post('module_type', 'AST'));
            $search = trim(preg_replace('/\s+/', ' ', (string)_post('search')));
            if ($search === '') {
                json_response(array('success' => true, 'message' => 'Enter item code or description to search.'));
            }

            if (!in_array($module_type, array('AST', 'CSM'), true)) {
                $module_type = 'AST';
            }
            $s = _esc('%' . $search . '%');

            if ($module_type === 'AST') {
                $sqlAny = "SELECT property_code, item_description, is_available
                           FROM ast_inventory
                           WHERE property_code LIKE '{$s}'
                              OR item_description LIKE '{$s}'
                              OR serial_number LIKE '{$s}'
                           ORDER BY property_code ASC
                           LIMIT 1";
                $anyRes = call_mysql_query($sqlAny);
                $any = $anyRes ? call_mysql_fetch_array($anyRes) : null;
                if ($any) {
                    $ia = (int)($any['is_available'] ?? 0);
                    if ($ia !== 1) {
                        json_response(array(
                            'success' => true,
                            'message' => "Found AST item {$any['property_code']}, but it is not available for assignment (is_available: {$ia})."
                        ));
                    }
                    json_response(array(
                        'success' => true,
                        'message' => "AST item {$any['property_code']} exists and is available. Please select it from the dropdown results."
                    ));
                }

                $altRes = call_mysql_query("SELECT inventory_system_item_code FROM csm_inventory
                                            WHERE inventory_system_item_code LIKE '{$s}' OR item_description LIKE '{$s}'
                                            LIMIT 1");
                if ($altRes && ($alt = call_mysql_fetch_array($altRes))) {
                    json_response(array('success' => true, 'message' => "No AST match. Found similar item in CSM ({$alt['inventory_system_item_code']}). Try switching module to CSM."));
                }
                json_response(array('success' => true, 'message' => 'No AST item matched your search.'));
            } else {
                $sqlAny = "SELECT inventory_system_item_code, item_description, status, current_unit_quantity
                           FROM csm_inventory
                           WHERE inventory_system_item_code LIKE '{$s}'
                              OR item_description LIKE '{$s}'
                           ORDER BY inventory_system_item_code ASC
                           LIMIT 1";
                $anyRes = call_mysql_query($sqlAny);
                $any = $anyRes ? call_mysql_fetch_array($anyRes) : null;
                if ($any) {
                    $qty = (int)($any['current_unit_quantity'] ?? 0);
                    $status = strtolower((string)($any['status'] ?? ''));
                    if ($status !== 'available' || $qty <= 0) {
                        json_response(array(
                            'success' => true,
                            'message' => "Found CSM item {$any['inventory_system_item_code']}, but it is not available for assignment (status: {$any['status']}, current qty: {$qty})."
                        ));
                    }
                    json_response(array(
                        'success' => true,
                        'message' => "CSM item {$any['inventory_system_item_code']} exists and is available. Please select it from the dropdown results."
                    ));
                }
                $altRes = call_mysql_query("SELECT property_code FROM ast_inventory
                                            WHERE property_code LIKE '{$s}' OR item_description LIKE '{$s}' OR serial_number LIKE '{$s}'
                                            LIMIT 1");
                if ($altRes && ($alt = call_mysql_fetch_array($altRes))) {
                    json_response(array('success' => true, 'message' => "No CSM match. Found similar item in AST ({$alt['property_code']}). Try switching module to AST."));
                }
                json_response(array('success' => true, 'message' => 'No CSM item matched your search.'));
            }
            break;

        case 'list_facility_inventory':
            $facility_id = _int(_post('facility_id'), 0);
            if ($facility_id <= 0) json_response(array('success' => false, 'message' => 'Facility is required.'), 422);
            $hasManagedBy = fr_column_exists('facility_records_assignments', 'managed_by_user_id');
            $sql = "SELECT
                        a.assignment_id,
                        a.module_type,
                        a.item_code,
                        a.item_description,
                        a.unit_id,
                        a.qty,
                        a.unit,
                        a.status,
                        a.issued_at,
                        a.returned_at,
                        a.remarks,
                        CONCAT(COALESCE(u1.f_name,''), ' ', COALESCE(u1.l_name,'')) AS issued_to_name,
                        CONCAT(COALESCE(u2.f_name,''), ' ', COALESCE(u2.l_name,'')) AS accountable_name,
                        " . ($hasManagedBy ? "CONCAT(COALESCE(u3.f_name,''), ' ', COALESCE(u3.l_name,''))" : "''") . " AS managed_by_name,
                        fu.unit_name,
                        fu.unit_code,
                        fu.floor_label,
                        ast_cat.category_photo AS ast_category_photo,
                        ast_cat.item_category_name AS ast_category_name,
                        csm_inv.item_category_img AS csm_category_img,
                        csm_cat.item_category_name AS csm_category_name
                    FROM facility_records_assignments a
                    LEFT JOIN users u1 ON u1.user_id = a.issued_to_user_id
                    LEFT JOIN users u2 ON u2.user_id = a.accountable_user_id
                    " . ($hasManagedBy ? "LEFT JOIN users u3 ON u3.user_id = a.managed_by_user_id" : "") . "
                    LEFT JOIN facility_records_units fu ON fu.unit_id = a.unit_id
                    LEFT JOIN ast_inventory ast_inv ON a.module_type = 'AST' AND a.item_code = ast_inv.property_code
                    LEFT JOIN ast_inventory_category ast_cat ON ast_inv.category_id = ast_cat.category_id
                    LEFT JOIN csm_inventory csm_inv ON a.module_type = 'CSM' AND a.item_code = csm_inv.inventory_system_item_code
                    LEFT JOIN csm_inventory_category csm_cat ON csm_inv.item_category_code = csm_cat.item_category_code
                    WHERE a.facility_id = {$facility_id}
                    ORDER BY a.created_at DESC";
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    fr_resolve_row_images($row);
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'list_unit_inventory':
            $unit_id = _int(_post('unit_id'), 0);
            if ($unit_id <= 0) json_response(array('success' => false, 'message' => 'Unit is required.'), 422);
            $hasManagedBy = fr_column_exists('facility_records_assignments', 'managed_by_user_id');
            $sql = "SELECT
                        a.assignment_id,
                        a.module_type,
                        a.item_code,
                        a.item_description,
                        a.unit_id,
                        a.qty,
                        a.unit,
                        a.status,
                        a.issued_at,
                        a.returned_at,
                        a.remarks,
                        CONCAT(COALESCE(u1.f_name,''), ' ', COALESCE(u1.l_name,'')) AS issued_to_name,
                        CONCAT(COALESCE(u2.f_name,''), ' ', COALESCE(u2.l_name,'')) AS accountable_name,
                        " . ($hasManagedBy ? "CONCAT(COALESCE(u3.f_name,''), ' ', COALESCE(u3.l_name,''))" : "''") . " AS managed_by_name,
                        fu.floor_label,
                        ast_cat.category_photo AS ast_category_photo,
                        ast_cat.item_category_name AS ast_category_name,
                        csm_inv.item_category_img AS csm_category_img,
                        csm_cat.item_category_name AS csm_category_name
                    FROM facility_records_assignments a
                    LEFT JOIN users u1 ON u1.user_id = a.issued_to_user_id
                    LEFT JOIN users u2 ON u2.user_id = a.accountable_user_id
                    " . ($hasManagedBy ? "LEFT JOIN users u3 ON u3.user_id = a.managed_by_user_id" : "") . "
                    LEFT JOIN facility_records_units fu ON fu.unit_id = a.unit_id
                    LEFT JOIN ast_inventory ast_inv ON a.module_type = 'AST' AND a.item_code = ast_inv.property_code
                    LEFT JOIN ast_inventory_category ast_cat ON ast_inv.category_id = ast_cat.category_id
                    LEFT JOIN csm_inventory csm_inv ON a.module_type = 'CSM' AND a.item_code = csm_inv.inventory_system_item_code
                    LEFT JOIN csm_inventory_category csm_cat ON csm_inv.item_category_code = csm_cat.item_category_code
                    WHERE a.unit_id = {$unit_id}
                    ORDER BY a.created_at DESC";
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    fr_resolve_row_images($row);
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'assign_item':
            global $s_user_id;
            $facility_id = _int(_post('facility_id'), 0);
            $unit_id = _int(_post('unit_id'), 0);
            $module_type = strtoupper(_post('module_type'));
            $source_item_id = _int(_post('source_item_id'), 0);
            $item_code = _post('item_code');
            $qty = _float(_post('qty'), 1);
            $remarks = _post('remarks');
            $issued_to_user_id = _int(_post('issued_to_user_id'), 0);
            // Business rule: accountable officer is the same as issued-to user.
            $accountable_user_id = $issued_to_user_id > 0 ? $issued_to_user_id : 0;
            $managed_by_user_id = _int(_post('managed_by_user_id'), 0);
            $hasManagedBy = fr_column_exists('facility_records_assignments', 'managed_by_user_id');

            if ($facility_id <= 0 || $unit_id <= 0 || $item_code === '' || !in_array($module_type, array('AST', 'CSM'), true)) {
                json_response(array('success' => false, 'message' => 'Invalid assignment payload.'), 422);
            }
            if ($qty <= 0) $qty = 1;

            // Ensure unit belongs to the selected facility.
            $unitRes = call_mysql_query("SELECT unit_id FROM facility_records_units WHERE unit_id = {$unit_id} AND facility_id = {$facility_id} LIMIT 1");
            if (!$unitRes || !call_mysql_fetch_array($unitRes)) {
                json_response(array('success' => false, 'message' => 'Selected unit does not belong to the facility.'), 422);
            }

            // Force managed-by from facility unit manager.
            $hasUnitManagersTable = fr_table_exists('facility_records_unit_managers');
            $hasUnitManager = fr_column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = fr_column_exists('facility_records_units', 'accountable_user_id');
            $unitManagerCol = $hasUnitManager ? 'facility_unit_manager_user_id' : ($hasUnitManagerLegacy ? 'accountable_user_id' : '');
            if ($hasUnitManagersTable) {
                $mgrRes = call_mysql_query("SELECT MIN(user_id) AS unit_manager_user_id
                                            FROM facility_records_unit_managers
                                            WHERE unit_id = {$unit_id}");
                $mgrRow = $mgrRes ? call_mysql_fetch_array($mgrRes) : null;
                $managed_by_user_id = (int)($mgrRow['unit_manager_user_id'] ?? 0);
            } elseif ($unitManagerCol !== '') {
                $mgrRes = call_mysql_query("SELECT {$unitManagerCol} AS unit_manager_user_id
                                            FROM facility_records_units
                                            WHERE unit_id = {$unit_id}
                                            LIMIT 1");
                $mgrRow = $mgrRes ? call_mysql_fetch_array($mgrRes) : null;
                $managed_by_user_id = (int)($mgrRow['unit_manager_user_id'] ?? 0);
            }

            $item_description = '';
            $item_unit = '';
            call_mysql_query("START TRANSACTION");
            if ($module_type === 'AST') {
                $qty = 1;
                $sql = "SELECT item_id, property_code, item_description, unit, is_available FROM ast_inventory WHERE item_id = {$source_item_id} AND property_code = '" . _esc($item_code) . "' LIMIT 1";
                $res = call_mysql_query($sql);
                $item = $res ? call_mysql_fetch_array($res) : null;
                if (!$item) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => 'AST item not found.'), 404);
                }
                if ((int)$item['is_available'] !== 1) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => 'AST item is no longer available.'), 422);
                }
                $item_description = $item['item_description'];
                $item_unit = $item['unit'];

                $lockOk = call_mysql_query("UPDATE ast_inventory
                                            SET is_available = 0
                                            WHERE item_id = {$source_item_id} AND is_available = 1
                                            LIMIT 1");
                if (!$lockOk || mysqli_affected_rows($db_connect) < 1) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => 'AST item is no longer available.'), 422);
                }
            } else {
                $sql = "SELECT inventory_id, inventory_system_item_code, item_description, current_unit_quantity
                        FROM csm_inventory
                        WHERE inventory_id = {$source_item_id} AND inventory_system_item_code = '" . _esc($item_code) . "'
                        LIMIT 1";
                $res = call_mysql_query($sql);
                $item = $res ? call_mysql_fetch_array($res) : null;
                if (!$item) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => 'CSM item not found.'), 404);
                }
                $available = (int)$item['current_unit_quantity'];
                if ($available < $qty) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => 'CSM quantity is not enough.'), 422);
                }
                $item_description = $item['item_description'];
                $item_unit = '';

                $lockOk = call_mysql_query("UPDATE csm_inventory
                                            SET current_unit_quantity = current_unit_quantity - " . (float)$qty . "
                                            WHERE inventory_id = {$source_item_id} AND current_unit_quantity >= " . (float)$qty . "
                                            LIMIT 1");
                if (!$lockOk || mysqli_affected_rows($db_connect) < 1) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => 'CSM quantity is not enough.'), 422);
                }
            }

            $insertCols = "facility_id, unit_id, module_type, source_item_id, item_code, item_description, qty, unit, issued_to_user_id, accountable_user_id";
            $insertVals = "{$facility_id}, {$unit_id}, '" . _esc($module_type) . "', {$source_item_id}, '" . _esc($item_code) . "', '" . _esc($item_description) . "', " . (float)$qty . ", '" . _esc($item_unit) . "', " . ($issued_to_user_id > 0 ? $issued_to_user_id : "NULL") . ", " . ($accountable_user_id > 0 ? $accountable_user_id : "NULL");
            if ($hasManagedBy) {
                $insertCols .= ", managed_by_user_id";
                $insertVals .= ", " . ($managed_by_user_id > 0 ? $managed_by_user_id : "NULL");
            }
            $insertCols .= ", status, issued_at, remarks, created_by, updated_by";
            $insertVals .= ", 'ACTIVE', NOW(), " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", " . (int)$s_user_id . ", " . (int)$s_user_id;
            $ok = call_mysql_query("INSERT INTO facility_records_assignments ({$insertCols}) VALUES ({$insertVals})");
            if (!$ok) {
                call_mysql_query("ROLLBACK");
                json_response(array('success' => false, 'message' => 'Failed to create assignment.'), 500);
            }
            $assignment_id = mysqli_insert_id($db_connect);
            $histOk = call_mysql_query("INSERT INTO facility_records_history (assignment_id, action, old_status, new_status, remarks, actor_user_id)
                                        VALUES ({$assignment_id}, 'ASSIGNED', NULL, 'ACTIVE', " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", " . (int)$s_user_id . ")");
            if (!$histOk) {
                call_mysql_query("ROLLBACK");
                json_response(array('success' => false, 'message' => 'Failed to record assignment history.'), 500);
            }
            call_mysql_query("COMMIT");

            activity_log_new("FACILITY ITEM ASSIGN", "SUCCESS", array(
                'assignment_id' => $assignment_id,
                'facility_id' => $facility_id,
                'unit_id' => $unit_id,
                'module_type' => $module_type,
                'item_code' => $item_code,
                'qty' => $qty,
                'issued_to_user_id' => $issued_to_user_id,
                'accountable_user_id' => $accountable_user_id,
                'managed_by_user_id' => $managed_by_user_id
            ));
            json_response(array('success' => true, 'message' => 'Item assigned to facility unit.'));
            break;

        case 'set_assignment_status':
            global $s_user_id;
            $assignment_id = _int(_post('assignment_id'), 0);
            $new_status = strtoupper(_post('status'));
            $remarks = _post('remarks');
            $allowed_status = array('ACTIVE', 'REPORTED', 'RETURN_REQUESTED', 'RETURNED');
            if ($assignment_id <= 0 || !in_array($new_status, $allowed_status, true)) {
                json_response(array('success' => false, 'message' => 'Invalid status payload.'), 422);
            }
            $res = call_mysql_query("SELECT * FROM facility_records_assignments WHERE assignment_id = {$assignment_id} LIMIT 1");
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) json_response(array('success' => false, 'message' => 'Assignment not found.'), 404);
            $old_status = strtoupper((string)$row['status']);

            call_mysql_query("START TRANSACTION");
            $ok = call_mysql_query("UPDATE facility_records_assignments
                                    SET status = '" . _esc($new_status) . "',
                                        returned_at = " . ($new_status === 'RETURNED' ? "NOW()" : "returned_at") . ",
                                        remarks = " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "remarks") . ",
                                        updated_by = " . (int)$s_user_id . "
                                    WHERE assignment_id = {$assignment_id}
                                    LIMIT 1");
            if (!$ok) {
                call_mysql_query("ROLLBACK");
                json_response(array('success' => false, 'message' => 'Failed to update assignment status.'), 500);
            }

            if ($new_status === 'RETURNED' && $old_status !== 'RETURNED') {
                $module_type = strtoupper((string)$row['module_type']);
                $source_item_id = (int)$row['source_item_id'];
                $qty = (float)$row['qty'];
                if ($module_type === 'AST') {
                    $invOk = call_mysql_query("UPDATE ast_inventory
                                               SET is_available = 1
                                               WHERE item_id = {$source_item_id}
                                               LIMIT 1");
                    if (!$invOk) {
                        call_mysql_query("ROLLBACK");
                        json_response(array('success' => false, 'message' => 'Failed to update AST inventory.'), 500);
                    }
                } elseif ($module_type === 'CSM') {
                    $invOk = call_mysql_query("UPDATE csm_inventory
                                               SET current_unit_quantity = current_unit_quantity + " . (float)$qty . "
                                               WHERE inventory_id = {$source_item_id}
                                               LIMIT 1");
                    if (!$invOk) {
                        call_mysql_query("ROLLBACK");
                        json_response(array('success' => false, 'message' => 'Failed to update CSM inventory.'), 500);
                    }
                }
            }

            $histOk = call_mysql_query("INSERT INTO facility_records_history (assignment_id, action, old_status, new_status, remarks, actor_user_id)
                                        VALUES ({$assignment_id}, 'STATUS_UPDATE', '" . _esc($old_status) . "', '" . _esc($new_status) . "', " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", " . (int)$s_user_id . ")");
            if (!$histOk) {
                call_mysql_query("ROLLBACK");
                json_response(array('success' => false, 'message' => 'Failed to record status history.'), 500);
            }
            call_mysql_query("COMMIT");
            activity_log_new("FACILITY ASSIGNMENT STATUS", "SUCCESS", array('assignment_id' => $assignment_id, 'old_status' => $old_status, 'new_status' => $new_status));
            json_response(array('success' => true, 'message' => 'Assignment status updated.'));
            break;

        default:
            json_response(array('success' => false, 'message' => 'Unknown action.'), 400);
    }
} catch (Throwable $e) {
    json_response(array('success' => false, 'message' => 'Server error occurred.'), 500);
}
