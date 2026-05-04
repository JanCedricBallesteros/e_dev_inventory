<?php
// admin/modules/nonconsumable/process/ast_physical_checking_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$isStaffAst = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access("AST"));
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

function get_last_insert_id_safe() {
    $res = call_mysql_query("SELECT LAST_INSERT_ID() AS id");
    $row = $res ? call_mysql_fetch_array($res) : null;
    return (int)($row['id'] ?? 0);
}

function table_exists($table) {
    $res = call_mysql_query("SHOW TABLES LIKE '" . _esc($table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function column_exists($table, $column) {
    $res = call_mysql_query("SHOW COLUMNS FROM `" . _esc($table) . "` LIKE '" . _esc($column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function normalize_check_condition($raw) {
    $v = trim((string)$raw);
    $allowed = array('Good', 'Damaged', 'Missing', 'Under Repair');
    return in_array($v, $allowed, true) ? $v : '';
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

function get_assignment_context($itemId, $propertyCode) {
    if (!table_exists('facility_records_assignments')) {
        return array();
    }
    $itemId = (int)$itemId;
    $propertyCodeEsc = _esc((string)$propertyCode);
    $hasManagedBy = column_exists('facility_records_assignments', 'managed_by_user_id');

    $hasRequisitionId = column_exists('facility_records_assignments', 'requisition_id');
    $sql = "SELECT
                a.assignment_id,
                a.status,
                a.issued_at,
                " . ($hasRequisitionId ? "a.requisition_id" : "NULL") . " AS requisition_id,
                " . ($hasManagedBy ? "a.managed_by_user_id" : "NULL") . " AS managed_by_user_id,
                f.facility_name,
                u.unit_name,
                CONCAT(COALESCE(iss.f_name,''), ' ', COALESCE(iss.l_name,'')) AS issued_to_name,
                CONCAT(COALESCE(acc.f_name,''), ' ', COALESCE(acc.l_name,'')) AS accountable_name,
                " . ($hasManagedBy ? "CONCAT(COALESCE(mgr.f_name,''), ' ', COALESCE(mgr.l_name,''))" : "''") . " AS managed_by_name
            FROM facility_records_assignments a
            LEFT JOIN facility_records_facilities f ON f.facility_id = a.facility_id
            LEFT JOIN facility_records_units u ON u.unit_id = a.unit_id
            LEFT JOIN users iss ON iss.user_id = a.issued_to_user_id
            LEFT JOIN users acc ON acc.user_id = a.accountable_user_id
            " . ($hasManagedBy ? "LEFT JOIN users mgr ON mgr.user_id = a.managed_by_user_id" : "") . "
            WHERE a.module_type = 'AST'
              AND (
                    (a.source_item_id = {$itemId} AND {$itemId} > 0)
                    OR a.item_code = '{$propertyCodeEsc}'
                  )
              AND a.status <> 'RETURNED'
            ORDER BY a.issued_at DESC, a.assignment_id DESC
            LIMIT 1";
    $res = call_mysql_query($sql);
    $row = $res ? call_mysql_fetch_array($res) : null;
    if (!$row) return array();

    $facilityName = trim((string)($row['facility_name'] ?? ''));
    $unitName = trim((string)($row['unit_name'] ?? ''));
    $facilityDisplay = trim($facilityName . ($unitName !== '' ? (' / ' . $unitName) : ''));

    $assignmentId = (int)($row['assignment_id'] ?? 0);
    $requisitionId = (int)($row['requisition_id'] ?? 0);
    $assignmentSource = $requisitionId > 0 ? 'Requisition Claim' : 'AST Issuance';
    if ($assignmentId > 0 && $requisitionId <= 0 && table_exists('requisition_items') && column_exists('requisition_items', 'claim_assignment_id')) {
        $rres = call_mysql_query("SELECT requisition_id
                                  FROM requisition_items
                                  WHERE claim_assignment_id = {$assignmentId}
                                  LIMIT 1");
        $rrow = $rres ? call_mysql_fetch_array($rres) : null;
        if ($rrow) {
            $assignmentSource = 'Requisition Claim';
        }
    }

    $extraManagers = '';
    if ($assignmentId > 0 && table_exists('facility_records_assignment_extra_managers')) {
        $eres = call_mysql_query("SELECT GROUP_CONCAT(
                                        DISTINCT TRIM(CONCAT(COALESCE(u.f_name,''), ' ', COALESCE(u.l_name,'')))
                                        ORDER BY u.f_name ASC, u.l_name ASC
                                        SEPARATOR ', '
                                    ) AS names
                                  FROM facility_records_assignment_extra_managers em
                                  LEFT JOIN users u ON u.user_id = em.user_id
                                  WHERE em.assignment_id = {$assignmentId}");
        $erow = $eres ? call_mysql_fetch_array($eres) : null;
        $extraManagers = trim((string)($erow['names'] ?? ''));
    }

    return array(
        'assignment_id' => $assignmentId,
        'assignment_status' => trim((string)($row['status'] ?? '')),
        'assignment_issued_at' => trim((string)($row['issued_at'] ?? '')),
        'assignment_source' => $assignmentSource,
        'facility' => $facilityDisplay,
        'accountable' => trim((string)($row['accountable_name'] ?? '')),
        'issued_to' => trim((string)($row['issued_to_name'] ?? '')),
        'managed_by' => trim((string)($row['managed_by_name'] ?? '')),
        'extra_managers' => $extraManagers
    );
}

function ensure_single_active_session($ignoreSessionId = 0) {
    $ignore = (int)$ignoreSessionId;
    $whereIgnore = $ignore > 0 ? "AND id <> {$ignore}" : "";
    $today = date('Y-m-d');
    $res = call_mysql_query("SELECT id, series_code
                             FROM ast_audit_sessions
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

function seed_session_items_if_missing($sessionId) {
    $sessionId = (int)$sessionId;
    if ($sessionId <= 0) return;

    $sql = "INSERT INTO ast_audit_checks
            (session_id, property_id, property_code, property_number, item_description, serial_number,
             quantity_checked, unit, date_stock, date_issued, status_at_check, facility, accountable,
             issued_to, managed_by, `condition`, remarks, checked_by, checked_at)
            SELECT
                {$sessionId} AS session_id,
                i.item_id AS property_id,
                i.property_code,
                COALESCE(i.property_number, ''),
                COALESCE(i.item_description, ''),
                COALESCE(i.serial_number, ''),
                1 AS quantity_checked,
                COALESCE(i.unit, ''),
                i.created_at AS date_stock,
                NULL AS date_issued,
                'NOT_CHECKED' AS status_at_check,
                '' AS facility,
                '' AS accountable,
                '' AS issued_to,
                '' AS managed_by,
                'Missing' AS `condition`,
                'Pending physical check.' AS remarks,
                " . (int)$GLOBALS['s_user_id'] . " AS checked_by,
                NOW() AS checked_at
            FROM ast_inventory i
            LEFT JOIN ast_audit_checks c
                ON c.session_id = {$sessionId}
               AND c.property_code = i.property_code
            WHERE c.id IS NULL";
    call_mysql_query($sql);
}

function reconcile_and_close_session($sessionId) {
    $sessionId = (int)$sessionId;
    if ($sessionId <= 0) return false;
    seed_session_items_if_missing($sessionId);

    // Any stale placeholders remain as Missing.
    $ok = call_mysql_query("UPDATE ast_audit_checks
                            SET `condition` = 'Missing',
                                status_at_check = CASE
                                    WHEN COALESCE(status_at_check, '') = '' THEN 'NOT_CHECKED'
                                    ELSE status_at_check
                                END,
                                remarks = CASE
                                    WHEN COALESCE(remarks, '') = '' THEN 'Not physically checked during this session.'
                                    ELSE remarks
                                END
                            WHERE session_id = {$sessionId}
                              AND `condition` = 'Missing'");
    return $ok ? true : false;
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
            if ($start_date > $end_date) {
                json_response(['success' => false, 'message' => 'End date must be after start date.'], 422);
            }
            $status = derive_session_status($start_date, $end_date);
            if ($status === 'Active') {
                $active = ensure_single_active_session(0);
                if ($active) {
                    json_response(['success' => false, 'message' => 'Only one active session is allowed. Active: ' . ($active['series_code'] ?? '#'.$active['id'])]);
                }
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
            $newId = get_last_insert_id_safe();
            if ($status === 'Active' && $newId > 0) {
                seed_session_items_if_missing($newId);
            }
            activity_log_new("AST PHYSICAL CHECK SESSION CREATE", "SUCCESS", array(
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
            $sql = "SELECT s.*, u.f_name, u.l_name
                    FROM ast_audit_sessions s
                    LEFT JOIN users u ON u.user_id = s.created_by
                    ORDER BY s.created_at DESC";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['status'] = sync_session_status(
                        (int)($row['id'] ?? 0),
                        (string)($row['start_date'] ?? ''),
                        (string)($row['end_date'] ?? ''),
                        (string)($row['status'] ?? '')
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
            $sql = "SELECT id, series_code, audit_name, start_date, end_date
                    FROM ast_audit_sessions
                    WHERE start_date <= '" . _esc($today) . "'
                      AND end_date >= '" . _esc($today) . "'
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
            $res = call_mysql_query("SELECT id, series_code, start_date, end_date, status
                                     FROM ast_audit_sessions
                                     WHERE id = {$session_id}
                                     LIMIT 1");
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) {
                json_response(['success' => false, 'message' => 'Session not found.'], 404);
            }

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $oldStart = (string)($row['start_date'] ?? '');
            $oldEnd = (string)($row['end_date'] ?? '');

            // Force date window to end before today so status resolves as Closed.
            $newEnd = ($oldEnd === '' || $oldEnd >= $today) ? $yesterday : $oldEnd;
            $newStart = $oldStart;
            if ($newStart === '' || $newStart > $newEnd) {
                $newStart = $newEnd;
            }

            call_mysql_query("START TRANSACTION");
            $ok = call_mysql_query("UPDATE ast_audit_sessions
                                    SET start_date = '" . _esc($newStart) . "',
                                        end_date = '" . _esc($newEnd) . "',
                                        status = 'Closed'
                                    WHERE id = {$session_id}
                                    LIMIT 1");
            if (!$ok) {
                call_mysql_query("ROLLBACK");
                json_response(['success' => false, 'message' => 'Failed to cancel session.'], 500);
            }
            if (!reconcile_and_close_session($session_id)) {
                call_mysql_query("ROLLBACK");
                json_response(['success' => false, 'message' => 'Failed to reconcile checks while cancelling.'], 500);
            }
            call_mysql_query("COMMIT");

            activity_log_new("AST PHYSICAL CHECK SESSION CANCEL", "SUCCESS", array(
                'session_id' => $session_id,
                'series_code' => $row['series_code'] ?? null,
                'old_start_date' => $oldStart,
                'old_end_date' => $oldEnd,
                'new_start_date' => $newStart,
                'new_end_date' => $newEnd
            ));

            json_response(['success' => true, 'message' => 'Session cancelled.']);
            break;
        }

        case 'get_item_by_code': {
            $property_code = _post('property_code');
            if ($property_code === '') {
                json_response(['success' => false, 'message' => 'Property Tag is required.'], 422);
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
            $ctx = get_assignment_context((int)($row['item_id'] ?? 0), (string)($row['property_code'] ?? ''));
            $row['status_at_check'] = $ctx['assignment_status'] ?? ($row['is_available'] ? 'IN_STOCK' : 'UNASSIGNED');
            $row['facility'] = $ctx['facility'] ?? '';
            $row['accountable'] = $ctx['accountable'] ?? '';
            $row['issued_to'] = $ctx['issued_to'] ?? '';
            $row['managed_by'] = $ctx['managed_by'] ?? '';
            $row['extra_managers'] = $ctx['extra_managers'] ?? '';
            $row['assignment_source'] = $ctx['assignment_source'] ?? (((int)($row['is_available'] ?? 0) === 1) ? 'Stockroom / Unassigned' : 'Unassigned');
            $row['assignment_issued_at'] = $ctx['assignment_issued_at'] ?? null;
            json_response(['success' => true, 'data' => $row]);
            break;
        }

        case 'create_check': {
            $session_id = _int(_post('session_id'));
            $property_code = _post('property_code');
            $quantity_checked = 1;
            $serial_number = _post('serial_number');
            $remarks = _post('remarks');
            $condition = normalize_check_condition(_post('condition'));
            $status_at_check = _post('status_at_check');
            $facility = _post('facility');
            $accountable = _post('accountable');
            $issued_to = _post('issued_to');
            $managed_by = _post('managed_by');
            $date_issued = _post('date_issued');

            if ($session_id <= 0 || $property_code === '') {
                json_response(['success' => false, 'message' => 'Session and Property Tag are required.'], 422);
            }

            $resSession = call_mysql_query("SELECT status, start_date, end_date FROM ast_audit_sessions WHERE id = {$session_id} LIMIT 1");
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
            seed_session_items_if_missing($session_id);
            $today = date('Y-m-d');
            $start = (string)($session['start_date'] ?? '');
            $end = (string)($session['end_date'] ?? '');
            if ($start !== '' && $end !== '' && ($today < $start || $today > $end)) {
                json_response(['success' => false, 'message' => 'Session is outside its audit date range.'], 422);
            }
            if ($condition === '') {
                json_response(['success' => false, 'message' => 'Condition is required.'], 422);
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

            $ctx = get_assignment_context($property_id, $property_code);
            $status_at_check = trim((string)($ctx['assignment_status'] ?? $status_at_check));
            if ($status_at_check === '') {
                $status_at_check = ((int)($item['is_available'] ?? 0) === 1) ? 'IN_STOCK' : 'UNASSIGNED';
            }
            $facility = trim((string)($ctx['facility'] ?? $facility));
            $accountable = trim((string)($ctx['accountable'] ?? $accountable));
            $issued_to = trim((string)($ctx['issued_to'] ?? $issued_to));
            $managed_by = trim((string)($ctx['managed_by'] ?? $managed_by));
            $extraManagers = trim((string)($ctx['extra_managers'] ?? ''));
            if ($extraManagers !== '') {
                $managed_by = $managed_by !== '' ? ($managed_by . ' | Extra: ' . $extraManagers) : ('Extra: ' . $extraManagers);
            }
            if ($date_issued === '') {
                $issuedAt = trim((string)($ctx['assignment_issued_at'] ?? ''));
                if ($issuedAt !== '') {
                    $date_issued = substr($issuedAt, 0, 10);
                }
            }

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
                    $managedByRaw = trim((string)($row['managed_by'] ?? ''));
                    $row['extra_managers'] = '';
                    if ($managedByRaw !== '' && strpos($managedByRaw, '| Extra: ') !== false) {
                        $parts = explode('| Extra: ', $managedByRaw, 2);
                        $row['managed_by'] = trim((string)($parts[0] ?? ''));
                        $row['extra_managers'] = trim((string)($parts[1] ?? ''));
                    }

                    $ctx = get_assignment_context((int)($row['property_id'] ?? 0), (string)($row['property_code'] ?? ''));
                    $row['assignment_source'] = trim((string)($ctx['assignment_source'] ?? ''));
                    if ($row['assignment_source'] === '') {
                        $row['assignment_source'] = (trim((string)($row['facility'] ?? '')) === '') ? 'Stockroom / Unassigned' : 'Assigned';
                    }
                    if ($row['extra_managers'] === '' && trim((string)($ctx['extra_managers'] ?? '')) !== '') {
                        $row['extra_managers'] = trim((string)($ctx['extra_managers'] ?? ''));
                    }
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

