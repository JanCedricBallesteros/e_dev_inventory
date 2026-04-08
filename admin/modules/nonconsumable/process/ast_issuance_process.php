<?php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$isStaffAst = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("AST", "PO")));
if (!isset($g_user_role) || (!role_has("ADMIN") && !$isStaffAst)) {
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

function table_exists($table)
{
    $res = call_mysql_query("SHOW TABLES LIKE '" . _esc($table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function column_exists($table, $column)
{
    $res = call_mysql_query("SHOW COLUMNS FROM `" . _esc($table) . "` LIKE '" . _esc($column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function first_existing_col($table, $candidates, $fallback = '')
{
    foreach ($candidates as $col) {
        if (column_exists($table, $col)) return $col;
    }
    return $fallback;
}

function require_core_tables()
{
    return table_exists('ast_inventory')
        && table_exists('facility_records_facilities')
        && table_exists('facility_records_units')
        && table_exists('facility_records_assignments')
        && table_exists('facility_records_history');
}

function stockroom_code()
{
    return 'STOCKROOM';
}

function stockroom_name()
{
    return 'Stockroom';
}

function get_stockroom_facility($ensure = true)
{
    global $s_user_id;
    $code = stockroom_code();
    $res = call_mysql_query("SELECT facility_id, facility_code, facility_name
                             FROM facility_records_facilities
                             WHERE UPPER(TRIM(facility_code)) = '" . _esc($code) . "'
                             ORDER BY facility_id ASC
                             LIMIT 1");
    $row = $res ? call_mysql_fetch_array($res) : null;
    if ($row) return $row;
    if (!$ensure) return null;

    $actor = isset($s_user_id) ? (int)$s_user_id : 0;
    $ok = call_mysql_query("INSERT INTO facility_records_facilities
                            (facility_code, facility_name, facility_floor, status, created_by, updated_by)
                            VALUES (
                                '" . _esc($code) . "',
                                '" . _esc(stockroom_name()) . "',
                                '" . _esc(json_encode(array('floors' => array()), JSON_UNESCAPED_UNICODE)) . "',
                                1,
                                " . ($actor > 0 ? $actor : "NULL") . ",
                                " . ($actor > 0 ? $actor : "NULL") . "
                            )");
    if (!$ok) return null;

    return array(
        'facility_id' => (int)mysqli_insert_id($GLOBALS['db_connect']),
        'facility_code' => $code,
        'facility_name' => stockroom_name()
    );
}

function is_stockroom_facility($facility_id)
{
    $facility_id = (int)$facility_id;
    if ($facility_id <= 0) return false;
    $res = call_mysql_query("SELECT facility_id
                             FROM facility_records_facilities
                             WHERE facility_id = {$facility_id}
                               AND UPPER(TRIM(facility_code)) = '" . _esc(stockroom_code()) . "'
                             LIMIT 1");
    return ($res && call_mysql_fetch_array($res)) ? true : false;
}

function active_user_where_clause()
{
    if (!column_exists('users', 'status')) {
        return "1=1";
    }
    $res1 = call_mysql_query("SELECT COUNT(*) AS cnt FROM users WHERE status = 1");
    $row1 = $res1 ? call_mysql_fetch_array($res1) : null;
    if ((int)($row1['cnt'] ?? 0) > 0) return "u.status = 1";

    $res0 = call_mysql_query("SELECT COUNT(*) AS cnt FROM users WHERE status = 0");
    $row0 = $res0 ? call_mysql_fetch_array($res0) : null;
    if ((int)($row0['cnt'] ?? 0) > 0) return "u.status = 0";

    return "1=1";
}

function ensure_extra_managers_table()
{
    $sql = "CREATE TABLE IF NOT EXISTS facility_records_assignment_extra_managers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assignment_id INT NOT NULL,
                user_id INT NOT NULL,
                created_by INT NULL,
                updated_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_assignment_user (assignment_id, user_id),
                KEY idx_assignment (assignment_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return call_mysql_query($sql) ? true : false;
}

$action = _post('action');
if ($action === '') {
    json_response(array('success' => false, 'message' => 'Missing action.'), 400);
}

if (!require_core_tables()) {
    json_response(array('success' => false, 'message' => 'Facility/AST tables are missing. Run migrations first.'), 500);
}

get_stockroom_facility(true);

try {
    switch ($action) {
        case 'list_available_ast_items':
            $search = trim(preg_replace('/\s+/', ' ', (string)_post('search')));
            $codeCol = first_existing_col('ast_inventory', array('property_code', 'item_code'), 'property_code');
            $propCol = first_existing_col('ast_inventory', array('property_number'), 'property_number');
            $serialCol = first_existing_col('ast_inventory', array('serial_number'), 'serial_number');
            $hasAvailFlag = column_exists('ast_inventory', 'is_available');
            $catIdCol = first_existing_col('ast_inventory', array('category_id'), 'category_id');

            $where = "WHERE 1=1";
            if ($hasAvailFlag) {
                $where .= " AND a.is_available = 1";
            }
            $where .= " AND NOT EXISTS (
                            SELECT 1
                            FROM facility_records_assignments x
                            WHERE x.module_type = 'AST'
                              AND x.source_item_id = a.item_id
                              AND x.status <> 'RETURNED'
                        )";

            if ($search !== '') {
                $s = _esc('%' . $search . '%');
                $where .= " AND (
                    a.{$codeCol} LIKE '{$s}'
                    OR a.{$propCol} LIKE '{$s}'
                    OR a.item_description LIKE '{$s}'
                    OR a.{$serialCol} LIKE '{$s}'
                )";
            }

            $sql = "SELECT
                        a.item_id AS source_item_id,
                        a.{$codeCol} AS item_code,
                        a.{$propCol} AS property_number,
                        a.item_description,
                        a.{$serialCol} AS serial_number,
                        a.unit,
                        cat.item_category_name,
                        cat.category_photo,
                        COALESCE(sr.facility_name, '" . _esc(stockroom_name()) . "') AS location_facility_name,
                        '' AS location_unit_name,
                        COALESCE(sr.facility_name, '" . _esc(stockroom_name()) . "') AS current_location
                    FROM ast_inventory a
                    LEFT JOIN ast_inventory_category cat ON cat.category_id = a.{$catIdCol}
                    LEFT JOIN facility_records_facilities sr ON UPPER(TRIM(sr.facility_code)) = '" . _esc(stockroom_code()) . "'
                    {$where}
                    ORDER BY a.{$codeCol} ASC";

            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $photo = trim((string)($row['category_photo'] ?? ''));
                    if ($photo !== '') {
                        $row['category_photo_url'] = BASE_URL . 'upload/category/' . $photo;
                        $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($photo) . '&s=100';
                    } else {
                        $row['category_photo_url'] = null;
                        $row['category_photo_thumb_url'] = null;
                    }
                    unset($row['category_photo']);
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'search_ast_item_by_code':
            $code = strtoupper(trim((string)_post('item_code')));
            if ($code === '') {
                json_response(array('success' => false, 'message' => 'Item code is required.'), 422);
            }

            $codeCol = first_existing_col('ast_inventory', array('property_code', 'item_code'), 'property_code');
            $propCol = first_existing_col('ast_inventory', array('property_number'), 'property_number');
            $serialCol = first_existing_col('ast_inventory', array('serial_number'), 'serial_number');
            $hasAvailFlag = column_exists('ast_inventory', 'is_available');
            $catIdCol = first_existing_col('ast_inventory', array('category_id'), 'category_id');
            $whereAvail = $hasAvailFlag ? "AND a.is_available = 1" : "";

            $sql = "SELECT
                        a.item_id AS source_item_id,
                        a.{$codeCol} AS item_code,
                        a.{$propCol} AS property_number,
                        a.item_description,
                        a.{$serialCol} AS serial_number,
                        a.unit,
                        cat.item_category_name,
                        cat.category_photo,
                        COALESCE(sr.facility_name, '" . _esc(stockroom_name()) . "') AS location_facility_name,
                        '' AS location_unit_name,
                        COALESCE(sr.facility_name, '" . _esc(stockroom_name()) . "') AS current_location
                    FROM ast_inventory a
                    LEFT JOIN ast_inventory_category cat ON cat.category_id = a.{$catIdCol}
                    LEFT JOIN facility_records_facilities sr ON UPPER(TRIM(sr.facility_code)) = '" . _esc(stockroom_code()) . "'
                    WHERE a.{$codeCol} = '" . _esc($code) . "'
                      {$whereAvail}
                      AND NOT EXISTS (
                            SELECT 1
                            FROM facility_records_assignments x
                            WHERE x.module_type = 'AST'
                              AND x.source_item_id = a.item_id
                              AND x.status <> 'RETURNED'
                        )
                    LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) {
                json_response(array('success' => false, 'message' => 'No issuable AST item found for that code.'), 404);
            }
            $photo = trim((string)($row['category_photo'] ?? ''));
            if ($photo !== '') {
                $row['category_photo_url'] = BASE_URL . 'upload/category/' . $photo;
                $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($photo) . '&s=100';
            } else {
                $row['category_photo_url'] = null;
                $row['category_photo_thumb_url'] = null;
            }
            unset($row['category_photo']);
            json_response(array('success' => true, 'data' => $row));
            break;

        case 'list_facilities':
            $sql = "SELECT
                        f.facility_id,
                        f.facility_code,
                        f.facility_name,
                        f.status,
                        CASE WHEN UPPER(TRIM(f.facility_code)) = '" . _esc(stockroom_code()) . "' THEN 1 ELSE 0 END AS is_stockroom
                    FROM facility_records_facilities f
                    WHERE f.status = 1 OR UPPER(TRIM(f.facility_code)) = '" . _esc(stockroom_code()) . "'
                    ORDER BY
                        CASE WHEN UPPER(TRIM(f.facility_code)) = '" . _esc(stockroom_code()) . "' THEN 0 ELSE 1 END,
                        f.facility_name ASC";
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'list_units':
            $facility_id = _int(_post('facility_id'), 0);
            if ($facility_id <= 0) {
                json_response(array('success' => false, 'message' => 'Facility is required.'), 422);
            }
            if (is_stockroom_facility($facility_id)) {
                json_response(array('success' => true, 'data' => array()));
            }

            $hasUnitManagersTable = table_exists('facility_records_unit_managers');
            $hasUnitManager = column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = column_exists('facility_records_units', 'accountable_user_id');
            $unitManagerCol = $hasUnitManager ? 'facility_unit_manager_user_id' : ($hasUnitManagerLegacy ? 'accountable_user_id' : '');

            if ($hasUnitManagersTable) {
                $sql = "SELECT
                            u.unit_id,
                            u.facility_id,
                            u.unit_code,
                            u.unit_name,
                            u.floor_label,
                            u.status,
                            MIN(um.user_id) AS managed_by_user_id,
                            GROUP_CONCAT(DISTINCT um.user_id ORDER BY ua.l_name SEPARATOR ',') AS manager_user_ids,
                            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) ORDER BY ua.l_name SEPARATOR ', ') AS manager_names
                        FROM facility_records_units u
                        LEFT JOIN facility_records_unit_managers um ON um.unit_id = u.unit_id
                        LEFT JOIN users ua ON ua.user_id = um.user_id
                        WHERE u.facility_id = {$facility_id} AND u.status = 1
                        GROUP BY u.unit_id
                        ORDER BY u.unit_name ASC";
            } else {
                $sql = "SELECT
                            u.unit_id,
                            u.facility_id,
                            u.unit_code,
                            u.unit_name,
                            u.floor_label,
                            u.status,
                            " . ($unitManagerCol !== '' ? "u.{$unitManagerCol}" : "NULL") . " AS managed_by_user_id,
                            " . ($unitManagerCol !== '' ? "CAST(u.{$unitManagerCol} AS CHAR)" : "''") . " AS manager_user_ids,
                            CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) AS manager_names
                        FROM facility_records_units u
                        LEFT JOIN users ua ON ua.user_id = " . ($unitManagerCol !== '' ? "u.{$unitManagerCol}" : "0") . "
                        WHERE u.facility_id = {$facility_id} AND u.status = 1
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

        case 'list_users':
            $search = _post('search');
            $limit = _int(_post('limit'), 100);
            if ($limit <= 0) $limit = 100;
            if ($limit > 500) $limit = 500;

            $where = "WHERE " . active_user_where_clause();
            $hasEmail = column_exists('users', 'email');
            $hasEmailAddress = column_exists('users', 'email_address');
            $emailExpr = $hasEmail ? "u.email" : ($hasEmailAddress ? "u.email_address" : "''");
            if ($search !== '') {
                $s = _esc('%' . $search . '%');
                $where .= " AND (u.f_name LIKE '{$s}' OR u.l_name LIKE '{$s}' OR u.username LIKE '{$s}' OR {$emailExpr} LIKE '{$s}')";
            }

            $sql = "SELECT
                        u.user_id,
                        {$emailExpr} AS email,
                        u.username,
                        u.position,
                        CONCAT(COALESCE(u.l_name,''), ', ', COALESCE(u.f_name,''), IF(COALESCE(u.m_name,'') <> '', CONCAT(' ', LEFT(u.m_name,1), '.'), '')) AS full_name
                    FROM users u
                    {$where}
                    ORDER BY u.l_name ASC, u.f_name ASC
                    LIMIT " . (int)$limit;
            $res = call_mysql_query($sql);
            $rows = array();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(array('success' => true, 'data' => $rows));
            break;

        case 'issue_ast_items_batch':
            global $s_user_id, $db_connect;
            $facility_id = _int(_post('facility_id'), 0);
            $unit_id = _int(_post('unit_id'), 0);
            $issued_to_user_id = _int(_post('issued_to_user_id'), 0);
            $remarks = _post('remarks');

            $itemsPayload = $_POST['selected_items'] ?? '[]';
            if (is_array($itemsPayload)) {
                $selectedItems = $itemsPayload;
            } else {
                $decoded = json_decode((string)$itemsPayload, true);
                $selectedItems = is_array($decoded) ? $decoded : array();
            }

            $extraManagersPayload = $_POST['extra_manager_user_ids'] ?? array();
            if (!is_array($extraManagersPayload)) {
                $decodedManagers = json_decode((string)$extraManagersPayload, true);
                if (is_array($decodedManagers)) {
                    $extraManagersPayload = $decodedManagers;
                } else {
                    $extraManagersPayload = array_filter(array_map('trim', explode(',', (string)$extraManagersPayload)));
                }
            }
            $extraManagerIds = array_values(array_unique(array_filter(array_map(function ($id) {
                return _int($id, 0);
            }, $extraManagersPayload))));

            if ($facility_id <= 0 || $unit_id <= 0) {
                json_response(array('success' => false, 'message' => 'Facility and unit are required.'), 422);
            }
            if ($issued_to_user_id <= 0) {
                json_response(array('success' => false, 'message' => 'Issued To is required.'), 422);
            }
            if (is_stockroom_facility($facility_id)) {
                json_response(array('success' => false, 'message' => 'Stockroom has no issuable units. Please select a regular facility unit.'), 422);
            }

            $unitRes = call_mysql_query("SELECT unit_id FROM facility_records_units WHERE unit_id = {$unit_id} AND facility_id = {$facility_id} LIMIT 1");
            if (!$unitRes || !call_mysql_fetch_array($unitRes)) {
                json_response(array('success' => false, 'message' => 'Selected unit does not belong to the selected facility.'), 422);
            }

            $normalizedItems = array();
            $seen = array();
            foreach ($selectedItems as $it) {
                if (!is_array($it)) continue;
                $sourceId = _int($it['source_item_id'] ?? 0, 0);
                $itemCode = strtoupper(trim((string)($it['item_code'] ?? '')));
                if ($sourceId <= 0 || $itemCode === '') continue;
                $key = $sourceId . '|' . $itemCode;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $normalizedItems[] = array(
                    'source_item_id' => $sourceId,
                    'item_code' => $itemCode
                );
            }

            if (count($normalizedItems) === 0) {
                json_response(array('success' => false, 'message' => 'Select at least one AST item to issue.'), 422);
            }

            if (!ensure_extra_managers_table()) {
                json_response(array('success' => false, 'message' => 'Unable to prepare extra-manager storage table.'), 500);
            }

            $managed_by_user_id = 0;
            $hasUnitManagersTable = table_exists('facility_records_unit_managers');
            $hasUnitManager = column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = column_exists('facility_records_units', 'accountable_user_id');
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

            $hasManagedByCol = column_exists('facility_records_assignments', 'managed_by_user_id');
            $actor = isset($s_user_id) ? (int)$s_user_id : 0;

            call_mysql_query("START TRANSACTION");
            $createdAssignmentIds = array();
            foreach ($normalizedItems as $entry) {
                $source_item_id = (int)$entry['source_item_id'];
                $item_code = $entry['item_code'];

                $itemSql = "SELECT item_id, property_code, item_description, unit, is_available
                            FROM ast_inventory
                            WHERE item_id = {$source_item_id}
                              AND property_code = '" . _esc($item_code) . "'
                            LIMIT 1
                            FOR UPDATE";
                $itemRes = call_mysql_query($itemSql);
                $itemRow = $itemRes ? call_mysql_fetch_array($itemRes) : null;
                if (!$itemRow) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => "AST item not found: {$item_code}."), 404);
                }

                if ((int)($itemRow['is_available'] ?? 0) !== 1) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => "AST item is no longer available: {$item_code}."), 422);
                }

                $activeRes = call_mysql_query("SELECT assignment_id
                                               FROM facility_records_assignments
                                               WHERE module_type = 'AST'
                                                 AND source_item_id = {$source_item_id}
                                                 AND status <> 'RETURNED'
                                               LIMIT 1
                                               FOR UPDATE");
                if ($activeRes && call_mysql_fetch_array($activeRes)) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => "AST item already has an active assignment: {$item_code}."), 422);
                }

                $lockOk = call_mysql_query("UPDATE ast_inventory
                                            SET is_available = 0
                                            WHERE item_id = {$source_item_id}
                                              AND is_available = 1
                                            LIMIT 1");
                if (!$lockOk || mysqli_affected_rows($db_connect) < 1) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => "AST item became unavailable during submit: {$item_code}."), 422);
                }

                $item_description = (string)($itemRow['item_description'] ?? '');
                $item_unit = (string)($itemRow['unit'] ?? '');
                $insertCols = "facility_id, unit_id, module_type, source_item_id, item_code, item_description, qty, unit, issued_to_user_id, accountable_user_id";
                $insertVals = "{$facility_id}, {$unit_id}, 'AST', {$source_item_id}, '" . _esc($item_code) . "', '" . _esc($item_description) . "', 1, '" . _esc($item_unit) . "', {$issued_to_user_id}, {$issued_to_user_id}";
                if ($hasManagedByCol) {
                    $insertCols .= ", managed_by_user_id";
                    $insertVals .= ", " . ($managed_by_user_id > 0 ? $managed_by_user_id : "NULL");
                }
                $insertCols .= ", status, issued_at, remarks, created_by, updated_by";
                $insertVals .= ", 'ACTIVE', NOW(), " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", " . ($actor > 0 ? $actor : "NULL") . ", " . ($actor > 0 ? $actor : "NULL");

                $assignOk = call_mysql_query("INSERT INTO facility_records_assignments ({$insertCols}) VALUES ({$insertVals})");
                if (!$assignOk) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => "Failed to create assignment for {$item_code}."), 500);
                }
                $assignment_id = (int)mysqli_insert_id($db_connect);
                $createdAssignmentIds[] = $assignment_id;

                $histOk = call_mysql_query("INSERT INTO facility_records_history
                                            (assignment_id, action, old_status, new_status, remarks, actor_user_id)
                                            VALUES
                                            ({$assignment_id}, 'ASSIGNED', NULL, 'ACTIVE', " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", " . ($actor > 0 ? $actor : "NULL") . ")");
                if (!$histOk) {
                    call_mysql_query("ROLLBACK");
                    json_response(array('success' => false, 'message' => "Failed to write assignment history for {$item_code}."), 500);
                }

                if (!empty($extraManagerIds)) {
                    foreach ($extraManagerIds as $uid) {
                        $uid = (int)$uid;
                        if ($uid <= 0) continue;
                        $xOk = call_mysql_query("INSERT IGNORE INTO facility_records_assignment_extra_managers
                                                (assignment_id, user_id, created_by, updated_by)
                                                VALUES
                                                ({$assignment_id}, {$uid}, " . ($actor > 0 ? $actor : "NULL") . ", " . ($actor > 0 ? $actor : "NULL") . ")");
                        if (!$xOk) {
                            call_mysql_query("ROLLBACK");
                            json_response(array('success' => false, 'message' => "Failed to save extra managers for {$item_code}."), 500);
                        }
                    }
                }
            }

            call_mysql_query("COMMIT");

            activity_log_new("AST ISSUANCE BATCH", "SUCCESS", array(
                'facility_id' => $facility_id,
                'unit_id' => $unit_id,
                'issued_to_user_id' => $issued_to_user_id,
                'managed_by_user_id' => $managed_by_user_id,
                'item_count' => count($normalizedItems),
                'assignment_ids' => $createdAssignmentIds,
                'extra_manager_user_ids' => $extraManagerIds
            ));

            json_response(array(
                'success' => true,
                'message' => count($normalizedItems) . ' item(s) issued successfully.',
                'data' => array(
                    'count' => count($normalizedItems),
                    'assignment_ids' => $createdAssignmentIds
                )
            ));
            break;

        default:
            json_response(array('success' => false, 'message' => 'Unknown action.'), 400);
    }
} catch (Throwable $e) {
    call_mysql_query("ROLLBACK");
    json_response(array('success' => false, 'message' => 'Server error: ' . $e->getMessage()), 500);
}
