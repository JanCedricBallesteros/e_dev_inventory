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

function _post($k, $default = '')
{
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default;
}

function _int($v, $default = 0)
{
    if ($v === '' || $v === null) {
        return $default;
    }
    return (int)$v;
}

function _esc($v)
{
    global $conn;
    $v = (string)$v;
    if (isset($conn) && $conn instanceof mysqli) {
        return mysqli_real_escape_string($conn, $v);
    }
    return addslashes($v);
}

function json_response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function get_last_insert_id_safe()
{
    $res = call_mysql_query("SELECT LAST_INSERT_ID() AS id");
    $row = $res ? call_mysql_fetch_array($res) : null;
    return (int)($row['id'] ?? 0);
}

function table_exists($table)
{
    $res = call_mysql_query("SHOW TABLES LIKE '" . _esc($table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function column_exists($table, $column)
{
    $res = call_mysql_query("SHOW COLUMNS FROM `" . str_replace('`', '``', (string)$table) . "` LIKE '" . _esc($column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function first_existing_column($table, $candidates, $fallback = '')
{
    foreach ((array)$candidates as $candidate) {
        if ($candidate !== '' && column_exists($table, $candidate)) {
            return $candidate;
        }
    }
    return $fallback;
}

function build_upload_url($pathOrFile)
{
    $pathOrFile = trim((string)$pathOrFile);
    if ($pathOrFile === '') {
        return null;
    }
    if (preg_match('~^(https?:)?//~i', $pathOrFile)) {
        return $pathOrFile;
    }
    $clean = ltrim($pathOrFile, '/');
    if (strpos($clean, 'upload/') === 0) {
        return BASE_URL . $clean;
    }
    return BASE_URL . 'upload/category/' . $clean;
}

function build_thumb_url($pathOrFile)
{
    $pathOrFile = trim((string)$pathOrFile);
    if ($pathOrFile === '') {
        return null;
    }
    $fileName = basename($pathOrFile);
    if ($fileName === '') {
        return null;
    }
    return BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($fileName) . '&s=100';
}

function parse_time_value($raw)
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return '';
    }
    $raw = substr($raw, 0, 5);
    $dt = DateTime::createFromFormat('H:i', $raw);
    if ($dt && $dt->format('H:i') === $raw) {
        return $raw;
    }
    return '';
}

function split_session_audit_name($auditName)
{
    $auditName = trim((string)$auditName);
    if (preg_match('/^\[TIME:([0-2]\d:\d{2})\|([0-2]\d:\d{2})\]\s*(.*)$/s', $auditName, $m)) {
        return array(
            'start_time' => parse_time_value($m[1]),
            'end_time' => parse_time_value($m[2]),
            'audit_name' => trim((string)($m[3] ?? '')),
        );
    }

    return array(
        'start_time' => '',
        'end_time' => '',
        'audit_name' => $auditName,
    );
}

function build_session_audit_name_with_meta($auditName, $startTime, $endTime)
{
    $parsed = split_session_audit_name($auditName);
    $displayName = trim((string)($parsed['audit_name'] ?? ''));
    $startTime = parse_time_value($startTime);
    $endTime = parse_time_value($endTime);

    if ($startTime === '' || $endTime === '') {
        return $displayName;
    }

    $prefix = '[TIME:' . $startTime . '|' . $endTime . ']';
    return $displayName !== '' ? ($prefix . ' ' . $displayName) : $prefix;
}

function get_session_time_storage_mode()
{
    static $mode = null;
    if ($mode !== null) {
        return $mode;
    }

    if (column_exists('csm_audit_sessions', 'start_datetime') && column_exists('csm_audit_sessions', 'end_datetime')) {
        $mode = 'datetime';
        return $mode;
    }

    if (column_exists('csm_audit_sessions', 'start_time') && column_exists('csm_audit_sessions', 'end_time')) {
        $mode = 'time';
        return $mode;
    }

    $mode = 'audit_name_meta';
    return $mode;
}

function build_session_window_label($startDate, $endDate, $startTime = '', $endTime = '')
{
    $startDate = trim((string)$startDate);
    $endDate = trim((string)$endDate);
    $startTime = parse_time_value($startTime);
    $endTime = parse_time_value($endTime);

    $startLabel = $startDate;
    if ($startTime !== '') {
        $startLabel .= ' ' . $startTime;
    }

    $endLabel = $endDate;
    if ($endTime !== '') {
        $endLabel .= ' ' . $endTime;
    }

    if ($startLabel === '' && $endLabel === '') {
        return '';
    }
    if ($startLabel === $endLabel) {
        return $startLabel;
    }
    return trim($startLabel . ' to ' . $endLabel);
}

function derive_session_status($startDate, $endDate, $startTime = '', $endTime = '')
{
    $startDate = trim((string)$startDate);
    $endDate = trim((string)$endDate);
    if ($startDate === '' || $endDate === '') {
        return 'Pending';
    }

    $startTime = parse_time_value($startTime);
    $endTime = parse_time_value($endTime);

    $startDt = strtotime($startDate . ' ' . ($startTime !== '' ? $startTime : '00:00') . ':00');
    $endDt = strtotime($endDate . ' ' . ($endTime !== '' ? $endTime : '23:59') . ':59');

    if (!$startDt || !$endDt || $endDt < $startDt) {
        $today = date('Y-m-d');
        if ($today < $startDate) {
            return 'Pending';
        }
        if ($today > $endDate) {
            return 'Closed';
        }
        return 'Active';
    }

    $now = time();
    if ($now < $startDt) {
        return 'Pending';
    }
    if ($now > $endDt) {
        return 'Closed';
    }
    return 'Active';
}

function enrich_session_row($row)
{
    $mode = get_session_time_storage_mode();
    $parsedAudit = split_session_audit_name($row['audit_name'] ?? '');
    $startDate = trim((string)($row['start_date'] ?? ''));
    $endDate = trim((string)($row['end_date'] ?? ''));
    $startTime = '';
    $endTime = '';

    if ($mode === 'datetime') {
        $startDatetime = trim((string)($row['start_datetime'] ?? ''));
        $endDatetime = trim((string)($row['end_datetime'] ?? ''));
        if ($startDatetime !== '') {
            $startDate = substr($startDatetime, 0, 10) ?: $startDate;
            $startTime = parse_time_value(substr($startDatetime, 11, 5));
        }
        if ($endDatetime !== '') {
            $endDate = substr($endDatetime, 0, 10) ?: $endDate;
            $endTime = parse_time_value(substr($endDatetime, 11, 5));
        }
    } elseif ($mode === 'time') {
        $startTime = parse_time_value($row['start_time'] ?? '');
        $endTime = parse_time_value($row['end_time'] ?? '');
    }

    if ($startTime === '' && !empty($parsedAudit['start_time'])) {
        $startTime = $parsedAudit['start_time'];
    }
    if ($endTime === '' && !empty($parsedAudit['end_time'])) {
        $endTime = $parsedAudit['end_time'];
    }

    $row['audit_name'] = trim((string)($parsedAudit['audit_name'] ?? ''));
    $row['start_date'] = $startDate;
    $row['end_date'] = $endDate;
    $row['start_time'] = $startTime;
    $row['end_time'] = $endTime;
    $row['start_display'] = trim($startDate . ($startTime !== '' ? (' ' . $startTime) : ''));
    $row['end_display'] = trim($endDate . ($endTime !== '' ? (' ' . $endTime) : ''));
    $row['window_label'] = build_session_window_label($startDate, $endDate, $startTime, $endTime);
    $row['status'] = derive_session_status($startDate, $endDate, $startTime, $endTime);

    return $row;
}

function get_session_by_id($sessionId)
{
    $sessionId = (int)$sessionId;
    if ($sessionId <= 0) {
        return null;
    }

    $res = call_mysql_query("SELECT * FROM csm_audit_sessions WHERE id = {$sessionId} LIMIT 1");
    $row = $res ? call_mysql_fetch_array($res) : null;
    if (!$row) {
        return null;
    }

    return enrich_session_row($row);
}

function ensure_single_active_session($ignoreSessionId = 0)
{
    $ignoreSessionId = (int)$ignoreSessionId;
    $res = call_mysql_query("SELECT * FROM csm_audit_sessions ORDER BY created_at DESC");
    if (!$res) {
        return null;
    }

    while ($row = call_mysql_fetch_array($res)) {
        if ((int)($row['id'] ?? 0) === $ignoreSessionId) {
            continue;
        }
        $row = enrich_session_row($row);
        if (($row['status'] ?? '') === 'Active') {
            return $row;
        }
    }

    return null;
}

function csm_inventory_field_map()
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = array(
        'cost' => first_existing_column('csm_inventory', array('cost_value', 'item_cost')),
        'total_qty' => first_existing_column('csm_inventory', array('quantity', 'unit_quantity')),
        'current_qty' => first_existing_column('csm_inventory', array('current_quantity', 'current_unit_quantity', 'quantity')),
        'crit_qty' => first_existing_column('csm_inventory', array('qty_crit_level', 'unit_crit_level')),
        'unit' => first_existing_column('csm_inventory', array('unit')),
        'status' => column_exists('csm_inventory', 'status') ? 'status' : '',
    );

    return $map;
}

function csm_audit_field_map()
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = array(
        'cost' => first_existing_column('csm_audit_checks', array('cost_value', 'item_cost')),
        'crit_qty' => first_existing_column('csm_audit_checks', array('qty_crit_level', 'unit_crit_level')),
    );

    return $map;
}

function derive_csm_stock_label($row)
{
    $currentQty = (int)($row['resolved_current_quantity'] ?? ($row['current_quantity'] ?? 0));
    $critLevel = (int)($row['resolved_crit_level'] ?? ($row['qty_crit_level'] ?? 0));
    $statusRaw = $row['resolved_system_status'] ?? ($row['status'] ?? 1);
    $statusText = strtolower(trim((string)$statusRaw));

    if ($statusText !== '' && !is_numeric($statusRaw)) {
        if (strpos($statusText, 'out') !== false) {
            return 'Out of Stock';
        }
        if (strpos($statusText, 'critical') !== false) {
            return 'Critical Stock';
        }
        if ($statusText !== 'available') {
            return 'Unavailable';
        }
    }

    if ($currentQty <= 0) {
        return 'Out of Stock';
    }
    if ($critLevel > 0 && $currentQty <= $critLevel) {
        return 'Critical Stock';
    }
    if (is_numeric($statusRaw) && (int)$statusRaw !== 1) {
        return 'Unavailable';
    }

    return 'Available';
}

function resolve_csm_item_image($item)
{
    $categoryId = (int)($item['category_id_ref'] ?? 0);
    $categoryImageId = (int)($item['category_image_id'] ?? 0);
    $itemCategoryImg = trim((string)($item['item_category_img'] ?? ''));
    $categoryPhoto = trim((string)($item['category_photo'] ?? ''));

    if ($categoryImageId <= 0 && $itemCategoryImg !== '' && ctype_digit($itemCategoryImg)) {
        $categoryImageId = (int)$itemCategoryImg;
    }

    $resolved = '';

    if ($categoryImageId > 0 && table_exists('csm_inventory_category_images')) {
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

    if ($resolved === '' && $categoryId > 0 && table_exists('csm_inventory_category_images')) {
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

    return array(
        'url' => $resolved !== '' ? build_upload_url($resolved) : null,
        'thumb' => $resolved !== '' ? build_thumb_url($resolved) : null,
    );
}

function fetch_csm_inventory_item($itemCode)
{
    $itemCode = trim((string)$itemCode);
    if ($itemCode === '') {
        return null;
    }

    $map = csm_inventory_field_map();
    $select = array(
        'i.*',
        'c.category_id AS category_id_ref',
        'c.item_category_name',
        'c.category_photo',
        ($map['cost'] !== '' ? "i.{$map['cost']} AS resolved_cost_value" : '0 AS resolved_cost_value'),
        ($map['total_qty'] !== '' ? "i.{$map['total_qty']} AS resolved_total_quantity" : '0 AS resolved_total_quantity'),
        ($map['current_qty'] !== '' ? "i.{$map['current_qty']} AS resolved_current_quantity" : '0 AS resolved_current_quantity'),
        ($map['crit_qty'] !== '' ? "i.{$map['crit_qty']} AS resolved_crit_level" : '0 AS resolved_crit_level'),
        ($map['unit'] !== '' ? "i.{$map['unit']} AS resolved_unit" : "'' AS resolved_unit"),
        ($map['status'] !== '' ? "i.{$map['status']} AS resolved_system_status" : '1 AS resolved_system_status'),
    );

    $itemCodeEsc = _esc($itemCode);
    $sql = "
        SELECT " . implode(",\n               ", $select) . "
        FROM csm_inventory i
        LEFT JOIN csm_inventory_category c
            ON c.item_category_code = i.item_category_code
        WHERE i.inventory_system_item_code = '{$itemCodeEsc}'
           OR i.qr_verification = '{$itemCodeEsc}'
        ORDER BY i.inventory_id DESC
        LIMIT 1
    ";
    $res = call_mysql_query($sql);
    $row = $res ? call_mysql_fetch_array($res) : null;
    if (!$row) {
        return null;
    }

    $row['resolved_cost_value'] = (float)($row['resolved_cost_value'] ?? 0);
    $row['resolved_total_quantity'] = (int)($row['resolved_total_quantity'] ?? 0);
    $row['resolved_current_quantity'] = (int)($row['resolved_current_quantity'] ?? 0);
    $row['resolved_crit_level'] = (int)($row['resolved_crit_level'] ?? 0);
    $row['resolved_unit'] = trim((string)($row['resolved_unit'] ?? ''));
    $row['resolved_system_status'] = $row['resolved_system_status'] ?? ($row['status'] ?? 1);

    $row['cost_value'] = $row['resolved_cost_value'];
    $row['quantity'] = $row['resolved_total_quantity'];
    $row['current_quantity'] = $row['resolved_current_quantity'];
    $row['qty_crit_level'] = $row['resolved_crit_level'];
    $row['unit'] = $row['resolved_unit'];
    $row['system_stock_label'] = derive_csm_stock_label($row);

    $img = resolve_csm_item_image($row);
    $row['item_image_url'] = $img['url'];
    $row['item_image_thumb_url'] = $img['thumb'];

    return $row;
}

function format_quantity_unit($qty, $unit = '')
{
    $qty = (int)$qty;
    $unit = trim((string)$unit);
    return $unit !== '' ? ($qty . ' ' . $unit) : (string)$qty;
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
            $start_time = parse_time_value(_post('start_time'));
            $end_time = parse_time_value(_post('end_time'));

            if ($start_date === '' || $end_date === '') {
                json_response(['success' => false, 'message' => 'Start and end dates are required.'], 422);
            }
            if ($start_time === '' || $end_time === '') {
                json_response(['success' => false, 'message' => 'Start and end times are required.'], 422);
            }

            $startTs = strtotime($start_date . ' ' . $start_time . ':00');
            $endTs = strtotime($end_date . ' ' . $end_time . ':59');
            if (!$startTs || !$endTs || $endTs < $startTs) {
                json_response(['success' => false, 'message' => 'End date/time must be after start date/time.'], 422);
            }

            $status = derive_session_status($start_date, $end_date, $start_time, $end_time);
            if ($status === 'Active') {
                $active = ensure_single_active_session(0);
                if ($active) {
                    json_response([
                        'success' => false,
                        'message' => 'Only one active session is allowed. Active: ' . ($active['series_code'] ?? ('#' . $active['id'])),
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
            $timeMode = get_session_time_storage_mode();

            $displayAuditName = trim((string)(split_session_audit_name($audit_name)['audit_name'] ?? ''));
            $storedAuditName = $displayAuditName;
            if ($timeMode === 'audit_name_meta') {
                $storedAuditName = build_session_audit_name_with_meta($displayAuditName, $start_time, $end_time);
            }

            $columns = array('series_code', 'audit_name', 'start_date', 'end_date', 'status', 'created_by', 'created_at');
            $values = array(
                "'" . _esc($series_code) . "'",
                $storedAuditName !== '' ? "'" . _esc($storedAuditName) . "'" : "NULL",
                "'" . _esc($start_date) . "'",
                "'" . _esc($end_date) . "'",
                "'" . _esc($status) . "'",
                (int)$GLOBALS['s_user_id'],
                'NOW()',
            );

            if ($timeMode === 'datetime') {
                $columns[] = 'start_datetime';
                $columns[] = 'end_datetime';
                $values[] = "'" . _esc($start_date . ' ' . $start_time . ':00') . "'";
                $values[] = "'" . _esc($end_date . ' ' . $end_time . ':59') . "'";
            } elseif ($timeMode === 'time') {
                $columns[] = 'start_time';
                $columns[] = 'end_time';
                $values[] = "'" . _esc($start_time) . "'";
                $values[] = "'" . _esc($end_time) . "'";
            }

            $sql = "
                INSERT INTO csm_audit_sessions
                (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $values) . ")
            ";

            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to create session.'], 500);
            }

            $newId = get_last_insert_id_safe();
            activity_log_new("CSM PHYSICAL CHECK SESSION CREATE", "SUCCESS", array(
                'session_id' => $newId,
                'series_code' => $series_code,
                'audit_name' => $displayAuditName,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'window_label' => build_session_window_label($start_date, $end_date, $start_time, $end_time),
                'status' => $status,
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
            $rows = array();

            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row = enrich_session_row($row);
                    $row['created_by_name'] = trim(($row['f_name'] ?? '') . ' ' . ($row['l_name'] ?? ''));
                    $rows[] = $row;
                }
            }

            json_response(['success' => true, 'data' => $rows]);
            break;
        }

        case 'list_active_sessions': {
            $res = call_mysql_query("
                SELECT id, series_code, audit_name, start_date, end_date,
                       " . (column_exists('csm_audit_sessions', 'start_time') ? 'start_time' : "NULL AS start_time") . ",
                       " . (column_exists('csm_audit_sessions', 'end_time') ? 'end_time' : "NULL AS end_time") . ",
                       " . (column_exists('csm_audit_sessions', 'start_datetime') ? 'start_datetime' : "NULL AS start_datetime") . ",
                       " . (column_exists('csm_audit_sessions', 'end_datetime') ? 'end_datetime' : "NULL AS end_datetime") . "
                FROM csm_audit_sessions
                ORDER BY created_at DESC
            ");
            $rows = array();

            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row = enrich_session_row($row);
                    if (($row['status'] ?? '') !== 'Active') {
                        continue;
                    }
                    $rows[] = $row;
                }
            }

            json_response(['success' => true, 'data' => $rows]);
            break;
        }

        case 'update_session_status': {
            json_response([
                'success' => false,
                'message' => 'Manual session status updates are disabled. Status is automatically derived from the configured date/time window.',
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

            $session = get_session_by_id($session_id);
            if (!$session) {
                json_response(['success' => false, 'message' => 'Session not found.'], 404);
            }

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $oldStart = (string)($session['start_date'] ?? '');
            $oldEnd = (string)($session['end_date'] ?? '');
            $oldStartTime = (string)($session['start_time'] ?? '');
            $oldEndTime = (string)($session['end_time'] ?? '');

            $newEnd = ($oldEnd === '' || $oldEnd >= $today) ? $yesterday : $oldEnd;
            $newStart = $oldStart;
            if ($newStart === '' || $newStart > $newEnd) {
                $newStart = $newEnd;
            }

            $setParts = array(
                "start_date = '" . _esc($newStart) . "'",
                "end_date = '" . _esc($newEnd) . "'",
                "status = 'Closed'",
            );

            $timeMode = get_session_time_storage_mode();
            $startTimeForSave = $oldStartTime !== '' ? $oldStartTime : '00:00';
            $endTimeForSave = $oldEndTime !== '' ? $oldEndTime : '23:59';

            if ($timeMode === 'datetime') {
                $setParts[] = "start_datetime = '" . _esc($newStart . ' ' . $startTimeForSave . ':00') . "'";
                $setParts[] = "end_datetime = '" . _esc($newEnd . ' ' . $endTimeForSave . ':59') . "'";
            }

            $sql = "
                UPDATE csm_audit_sessions
                SET " . implode(",\n                    ", $setParts) . "
                WHERE id = {$session_id}
                LIMIT 1
            ";

            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to cancel session.'], 500);
            }

            activity_log_new("CSM PHYSICAL CHECK SESSION CANCEL", "SUCCESS", array(
                'session_id' => $session_id,
                'series_code' => $session['series_code'] ?? null,
                'old_start_date' => $oldStart,
                'old_end_date' => $oldEnd,
                'old_start_time' => $oldStartTime,
                'old_end_time' => $oldEndTime,
                'new_start_date' => $newStart,
                'new_end_date' => $newEnd,
            ));

            json_response(['success' => true, 'message' => 'Session cancelled.']);
            break;
        }

        case 'get_item_by_code': {
            $item_code = _post('item_code');
            if ($item_code === '') {
                $item_code = _post('property_code');
            }

            if ($item_code === '') {
                json_response(['success' => false, 'message' => 'Inventory item code is required.'], 422);
            }

            $row = fetch_csm_inventory_item($item_code);
            if (!$row) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }

            json_response(['success' => true, 'data' => $row]);
            break;
        }

        case 'create_check': {
            $session_id = _int(_post('session_id'));
            $item_code = _post('item_code');
            if ($item_code === '') {
                $item_code = _post('property_code');
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

            $session = get_session_by_id($session_id);
            if (!$session) {
                json_response(['success' => false, 'message' => 'Session not found.'], 404);
            }
            if (($session['status'] ?? '') !== 'Active') {
                json_response(['success' => false, 'message' => 'Only active sessions allow checking.'], 422);
            }

            $item = fetch_csm_inventory_item($item_code);
            if (!$item) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }

            $auditMap = csm_audit_field_map();
            $inventory_id = (int)($item['inventory_id'] ?? 0);
            $inventory_system_item_code = trim((string)($item['inventory_system_item_code'] ?? ''));
            $item_category_code = trim((string)($item['item_category_code'] ?? ''));
            $item_description = trim((string)($item['item_description'] ?? ($item['item_name'] ?? '')));
            $acquisition_date = trim((string)($item['acquisition_date'] ?? ''));
            $cost_value = (float)($item['resolved_cost_value'] ?? 0);
            $source_of_funds = trim((string)($item['source_of_funds'] ?? ''));
            $system_unit_quantity = (int)($item['resolved_total_quantity'] ?? 0);
            $system_current_quantity = (int)($item['resolved_current_quantity'] ?? 0);
            $qty_crit_level = (int)($item['resolved_crit_level'] ?? 0);
            $system_status_raw = $item['resolved_system_status'] ?? ($item['status'] ?? 1);
            $system_status = 1;
            if (is_numeric($system_status_raw)) {
                $system_status = (int)$system_status_raw;
            } elseif (strtolower(trim((string)$system_status_raw)) !== 'available') {
                $system_status = 0;
            }
            $item_unit = trim((string)($item['resolved_unit'] ?? ''));
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

            $setParts = array(
                "inventory_id = {$inventory_id}",
                "item_category_code = '" . _esc($item_category_code) . "'",
                "item_description = '" . _esc($item_description) . "'",
                "acquisition_date = " . ($acquisition_date !== '' ? "'" . _esc($acquisition_date) . "'" : "NULL"),
                "source_of_funds = '" . _esc($source_of_funds) . "'",
                "system_unit_quantity = {$system_unit_quantity}",
                "system_current_quantity = {$system_current_quantity}",
                "counted_quantity = {$counted_quantity}",
                "variance_quantity = {$variance_quantity}",
                "system_status = {$system_status}",
                "status_at_check = '" . _esc($status_at_check) . "'",
                "`condition` = '" . _esc($condition) . "'",
                "storage_location = '" . _esc($storage_location) . "'",
                "remarks = '" . _esc($remarks) . "'",
                "checked_by = {$checked_by}",
                "checked_at = NOW()",
            );

            if ($auditMap['cost'] !== '') {
                $setParts[] = "{$auditMap['cost']} = " . number_format($cost_value, 2, '.', '');
            }
            if ($auditMap['crit_qty'] !== '') {
                $setParts[] = "{$auditMap['crit_qty']} = {$qty_crit_level}";
            }

            if ($existingRow) {
                $prevRes = call_mysql_query("
                    SELECT *
                    FROM csm_audit_checks
                    WHERE id = " . (int)$existingRow['id'] . "
                    LIMIT 1
                ");
                $prev = $prevRes ? call_mysql_fetch_array($prevRes) : null;

                $sql = "
                    UPDATE csm_audit_checks
                    SET " . implode(",\n                        ", $setParts) . "
                    WHERE id = " . (int)$existingRow['id'] . "
                    LIMIT 1
                ";
            } else {
                $columns = array(
                    'session_id',
                    'inventory_id',
                    'inventory_system_item_code',
                    'item_category_code',
                    'item_description',
                    'acquisition_date',
                    'source_of_funds',
                    'system_unit_quantity',
                    'system_current_quantity',
                    'counted_quantity',
                    'variance_quantity',
                    'system_status',
                    'status_at_check',
                    '`condition`',
                    'storage_location',
                    'remarks',
                    'checked_by',
                    'checked_at',
                );
                $values = array(
                    $session_id,
                    $inventory_id,
                    "'" . _esc($inventory_system_item_code) . "'",
                    "'" . _esc($item_category_code) . "'",
                    "'" . _esc($item_description) . "'",
                    ($acquisition_date !== '' ? "'" . _esc($acquisition_date) . "'" : "NULL"),
                    "'" . _esc($source_of_funds) . "'",
                    $system_unit_quantity,
                    $system_current_quantity,
                    $counted_quantity,
                    $variance_quantity,
                    $system_status,
                    "'" . _esc($status_at_check) . "'",
                    "'" . _esc($condition) . "'",
                    "'" . _esc($storage_location) . "'",
                    "'" . _esc($remarks) . "'",
                    $checked_by,
                    'NOW()',
                );

                if ($auditMap['cost'] !== '') {
                    $columns[] = $auditMap['cost'];
                    $values[] = number_format($cost_value, 2, '.', '');
                }
                if ($auditMap['crit_qty'] !== '') {
                    $columns[] = $auditMap['crit_qty'];
                    $values[] = $qty_crit_level;
                }

                $sql = "
                    INSERT INTO csm_audit_checks
                    (" . implode(', ', $columns) . ")
                    VALUES (" . implode(', ', $values) . ")
                ";
            }

            if (!call_mysql_query($sql)) {
                json_response(['success' => false, 'message' => 'Failed to save check.'], 500);
            }

            activity_log_new($existingRow ? "CSM PHYSICAL CHECK UPDATE" : "CSM PHYSICAL CHECK CREATE", "SUCCESS", array(
                'session_id' => $session_id,
                'series_code' => $session['series_code'] ?? null,
                'inventory_id' => $inventory_id,
                'inventory_system_item_code' => $inventory_system_item_code,
                'item_category_code' => $item_category_code,
                'item_description' => $item_description,
                'item_unit' => $item_unit,
                'system_unit_quantity' => $system_unit_quantity,
                'system_current_quantity' => $system_current_quantity,
                'issued_quantity' => $system_unit_quantity - $system_current_quantity,
                'counted_quantity' => $counted_quantity,
                'variance_quantity' => $variance_quantity,
                'qty_crit_level' => $qty_crit_level,
                'status_at_check' => $status_at_check,
                'condition' => $condition,
                'storage_location' => $storage_location,
                'remarks' => $remarks,
                'previous' => isset($prev) && is_array($prev) ? $prev : null,
            ));

            json_response(['success' => true, 'message' => 'Check saved.']);
            break;
        }

        case 'list_checks': {
            $session_id = _int(_post('session_id'));
            $search = _post('search');
            $inventoryMap = csm_inventory_field_map();

            $where = array();
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
                    OR c.remarks LIKE '{$searchEsc}'
                    OR s.series_code LIKE '{$searchEsc}'
                    OR cat.item_category_name LIKE '{$searchEsc}'
                )";
            }
            $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

            $unitSelect = $inventoryMap['unit'] !== '' ? "inv.{$inventoryMap['unit']} AS inventory_unit" : "'' AS inventory_unit";
            $categoryImageSelect = column_exists('csm_inventory', 'category_image_id') ? 'inv.category_image_id' : '0 AS category_image_id';

            $sql = "
                SELECT c.*, s.series_code, u.f_name, u.l_name,
                       {$unitSelect},
                       inv.item_category_img,
                       {$categoryImageSelect},
                       cat.category_id AS category_id_ref,
                       cat.item_category_name,
                       cat.category_photo
                FROM csm_audit_checks c
                LEFT JOIN csm_audit_sessions s ON s.id = c.session_id
                LEFT JOIN users u ON u.user_id = c.checked_by
                LEFT JOIN csm_inventory inv ON inv.inventory_id = c.inventory_id
                LEFT JOIN csm_inventory_category cat
                    ON cat.item_category_code = COALESCE(NULLIF(inv.item_category_code, ''), c.item_category_code)
                {$whereSql}
                ORDER BY c.checked_at DESC
            ";
            $res = call_mysql_query($sql);
            $rows = array();

            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['checked_by_name'] = trim(($row['f_name'] ?? '') . ' ' . ($row['l_name'] ?? ''));
                    $row['item_unit'] = trim((string)($row['inventory_unit'] ?? ''));
                    $row['on_hand_quantity'] = (int)($row['counted_quantity'] ?? 0);
                    $row['issued_quantity'] = (int)($row['system_unit_quantity'] ?? 0) - (int)($row['system_current_quantity'] ?? 0);
                    $row['total_quantity'] = (int)($row['system_unit_quantity'] ?? 0);
                    $row['discrepancy_quantity'] = (int)($row['variance_quantity'] ?? 0);
                    $row['date_check'] = (string)($row['checked_at'] ?? '');
                    $row['on_hand_quantity_display'] = format_quantity_unit($row['on_hand_quantity'], $row['item_unit']);
                    $row['issued_quantity_display'] = format_quantity_unit($row['issued_quantity'], $row['item_unit']);
                    $row['total_quantity_display'] = format_quantity_unit($row['total_quantity'], $row['item_unit']);
                    $row['discrepancy_quantity_display'] = format_quantity_unit($row['discrepancy_quantity'], $row['item_unit']);

                    $img = resolve_csm_item_image(array(
                        'category_id_ref' => $row['category_id_ref'] ?? 0,
                        'category_image_id' => $row['category_image_id'] ?? 0,
                        'item_category_img' => $row['item_category_img'] ?? '',
                        'category_photo' => $row['category_photo'] ?? '',
                    ));
                    $row['item_image_url'] = $img['url'];
                    $row['item_image_thumb_url'] = $img['thumb'];

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
