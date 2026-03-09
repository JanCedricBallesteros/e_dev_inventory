<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$staffAccess = (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("AST", "CSM"));
if (!(
    role_has("ADMIN") ||
    $staffAccess
)) {
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
function _json_array($v) {
    if ($v === null || $v === '') return [];
    if (is_array($v)) return $v;
    $decoded = json_decode((string)$v, true);
    return is_array($decoded) ? $decoded : [];
}

function normalize_position_category($position) {
    $p = strtolower(trim((string)$position));
    if ($p === '') return '';
    if (strpos($p, 'non') !== false && strpos($p, 'teaching') !== false) return 'non_teaching';
    if (strpos($p, 'non-teaching') !== false || strpos($p, 'non teaching') !== false || strpos($p, 'nonteaching') !== false) return 'non_teaching';
    if (strpos($p, 'teaching') !== false) return 'teaching';
    return '';
}

function normalize_allowed_employment($raw) {
    if ($raw === null || $raw === '') {
        return ['mode' => 'all', 'teaching' => [], 'non_teaching' => []];
    }
    $decoded = is_array($raw) ? $raw : json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        return ['mode' => 'all', 'teaching' => [], 'non_teaching' => []];
    }
    if (array_key_exists('teaching', $decoded) || array_key_exists('non_teaching', $decoded) || array_key_exists('none', $decoded)) {
        $teaching = array_values(array_filter(array_map('intval', (array)($decoded['teaching'] ?? []))));
        $non = array_values(array_filter(array_map('intval', (array)($decoded['non_teaching'] ?? []))));
        $none = !empty($decoded['none']);
        if ($none) return ['mode' => 'none', 'teaching' => [], 'non_teaching' => []];
        if (empty($teaching) && empty($non)) return ['mode' => 'none', 'teaching' => [], 'non_teaching' => []];
        return ['mode' => 'structured', 'teaching' => $teaching, 'non_teaching' => $non];
    }
    $list = array_values($decoded);
    $listStr = array_map('strval', $list);
    if (in_array('NONE', $listStr, true)) {
        return ['mode' => 'none', 'teaching' => [], 'non_teaching' => []];
    }
    $ids = array_values(array_filter(array_map('intval', $list)));
    if (empty($ids)) return ['mode' => 'all', 'teaching' => [], 'non_teaching' => []];
    return ['mode' => 'legacy', 'teaching' => $ids, 'non_teaching' => $ids];
}
function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function table_exists($table) {
    $res = call_mysql_query("SHOW TABLES LIKE '" . _esc($table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function table_column_exists($table, $column) {
    $res = call_mysql_query("SHOW COLUMNS FROM `" . _esc($table) . "` LIKE '" . _esc($column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function safe_int($v) {
    $n = (int)$v;
    return $n > 0 ? $n : null;
}

function active_user_where_clause() {
    if (!table_column_exists('users', 'status')) {
        return "1=1";
    }
    $r1 = call_mysql_query("SELECT COUNT(*) AS cnt FROM users WHERE status = 1");
    $d1 = $r1 ? call_mysql_fetch_array($r1) : null;
    $c1 = (int)($d1['cnt'] ?? 0);
    if ($c1 > 0) return "u.status = 1";
    $r0 = call_mysql_query("SELECT COUNT(*) AS cnt FROM users WHERE status = 0");
    $d0 = $r0 ? call_mysql_fetch_array($r0) : null;
    $c0 = (int)($d0['cnt'] ?? 0);
    if ($c0 > 0) return "u.status = 0";
    return "1=1";
}

$action = _post('action', '');
if ($action === '') {
    json_response(['success' => false, 'message' => 'Missing action.'], 400);
}

try {
    switch ($action) {
        case 'list_requisitions':
            $type = strtoupper(_post('type'));
            $status = strtolower(_post('status'));
            $search = _post('search');
            if (!in_array($type, ['AST', 'CSM'], true)) {
                $type = 'AST';
            }

            $where = "WHERE r.module_type = '" . _esc($type) . "'";
            if ($status !== '') {
                $where .= " AND r.status = '" . _esc($status) . "'";
            }
            if ($search !== '') {
                $searchEsc = _esc('%' . $search . '%');
                $where .= " AND (r.item_code LIKE '{$searchEsc}' OR r.item_description LIKE '{$searchEsc}' OR u.f_name LIKE '{$searchEsc}' OR u.l_name LIKE '{$searchEsc}')";
            }

            $hasClaimAssignment = table_column_exists('requisition_items', 'claim_assignment_id');
            $hasClaimedAt = table_column_exists('requisition_items', 'claimed_at');
            $sql = "SELECT 
                        r.requisition_id,
                        r.module_type,
                        r.item_code,
                        r.item_description,
                        r.qty_requested,
                        r.status,
                        " . ($hasClaimAssignment ? "r.claim_assignment_id," : "NULL AS claim_assignment_id,") . "
                        " . ($hasClaimedAt ? "r.claimed_at," : "NULL AS claimed_at,") . "
                        r.created_at,
                        r.requester_user_id,
                        u.user_id,
                        u.f_name,
                        u.m_name,
                        u.l_name,
                        u.suffix,
                        u.employment_status_id,
                        es.status_name AS employment_status
                    FROM requisition_items r
                    LEFT JOIN users u ON u.user_id = r.requester_user_id
                    LEFT JOIN employment_status es ON es.employment_status_id = u.employment_status_id
                    {$where}
                    ORDER BY r.created_at DESC";

            $res = call_mysql_query($sql);
            if ($res === false) {
                json_response(['success' => false, 'message' => 'Requisition table not found or query failed.'], 200);
            }
            $rows = [];
            while ($row = call_mysql_fetch_array($res)) {
                $row['requester_name'] = get_full_name($row['f_name'], $row['m_name'], $row['l_name'], $row['suffix']);
                $rawStatus = strtolower((string)($row['status'] ?? 'pending'));
                if (!empty($row['claim_assignment_id']) || !empty($row['claimed_at'])) {
                    $row['workflow_status'] = 'claimed';
                } elseif ($rawStatus === 'approved') {
                    $row['workflow_status'] = 'for_claiming';
                } else {
                    $row['workflow_status'] = $rawStatus;
                }
                $rows[] = $row;
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'approve_requisition':
            $id = _int(_post('requisition_id'));
            if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid requisition.'], 422);

            $sql = "SELECT r.*, u.employment_status_id, u.position
                    FROM requisition_items r
                    LEFT JOIN users u ON u.user_id = r.requester_user_id
                    WHERE r.requisition_id = {$id} LIMIT 1";
            $res = call_mysql_query($sql);
            $req = $res ? call_mysql_fetch_array($res) : null;
            if (!$req) json_response(['success' => false, 'message' => 'Requisition not found.'], 404);
            $prevStatus = $req['status'] ?? '';
            $availableQty = null;
            $newAvailable = null;

            // Enforce AST availability rules
            if (strtoupper($req['module_type']) === 'AST') {
                $itemCode = _esc($req['item_code']);
                $inv = call_mysql_query("SELECT available_qty, allowed_employment_status FROM ast_inventory WHERE property_code = '{$itemCode}' LIMIT 1");
                $invRow = $inv ? call_mysql_fetch_array($inv) : null;
                if (!$invRow) json_response(['success' => false, 'message' => 'AST item not found.'], 404);
                $availableQty = (int)($invRow['available_qty'] ?? 0);
                $reqQty = (int)($req['qty_requested'] ?? 0);
                if ($reqQty <= 0) json_response(['success' => false, 'message' => 'Invalid requested quantity.'], 422);
                if ($reqQty > $availableQty) {
                    json_response(['success' => false, 'message' => 'Available quantity not enough for this request.'], 422);
                }
                $norm = normalize_allowed_employment($invRow['allowed_employment_status'] ?? '');
                if ($norm['mode'] === 'none') {
                    json_response(['success' => false, 'message' => 'Requester is not allowed for this item.'], 422);
                }
                if ($norm['mode'] !== 'all') {
                    $statusId = (int)($req['employment_status_id'] ?? 0);
                    $posCat = normalize_position_category($req['position'] ?? '');
                    if ($statusId <= 0) {
                        json_response(['success' => false, 'message' => 'Requester employment status not allowed.'], 422);
                    }
                    if ($norm['mode'] === 'legacy') {
                        if (!in_array($statusId, $norm['teaching'], true)) {
                            json_response(['success' => false, 'message' => 'Requester employment status not allowed.'], 422);
                        }
                    } else {
                        if ($posCat === 'teaching') {
                            if (!in_array($statusId, $norm['teaching'], true)) {
                                json_response(['success' => false, 'message' => 'Requester employment status not allowed.'], 422);
                            }
                        } elseif ($posCat === 'non_teaching') {
                            if (!in_array($statusId, $norm['non_teaching'], true)) {
                                json_response(['success' => false, 'message' => 'Requester employment status not allowed.'], 422);
                            }
                        } else {
                            json_response(['success' => false, 'message' => 'Requester position not allowed.'], 422);
                        }
                    }
                }
                $newAvailable = $availableQty - $reqQty;
                call_mysql_query("UPDATE ast_inventory SET available_qty = {$newAvailable}, is_available = " . ($newAvailable > 0 ? 1 : 0) . " WHERE property_code = '{$itemCode}' LIMIT 1");
            }

            $setCols = "status='approved', updated_at = NOW()";
            if (table_column_exists('requisition_items', 'approved_by_user_id')) {
                $setCols .= ", approved_by_user_id = " . (int)$s_user_id;
            }
            if (table_column_exists('requisition_items', 'approved_at')) {
                $setCols .= ", approved_at = NOW()";
            }
            $ok = call_mysql_query("UPDATE requisition_items SET {$setCols} WHERE requisition_id = {$id} LIMIT 1");
            if (!$ok) json_response(['success' => false, 'message' => 'Failed to approve requisition.'], 500);
            activity_log_new("REQUISITION APPROVAL", "SUCCESS", array(
                'requisition_id' => $id,
                'module_type' => $req['module_type'] ?? '',
                'item_code' => $req['item_code'] ?? '',
                'item_description' => $req['item_description'] ?? '',
                'qty_requested' => (int)($req['qty_requested'] ?? 0),
                'requester_user_id' => (int)($req['requester_user_id'] ?? 0),
                'old_status' => $prevStatus,
                'new_status' => 'approved',
                'available_qty_before' => $availableQty,
                'available_qty_after' => $newAvailable
            ));
            json_response(['success' => true, 'message' => 'Requisition approved.']);
            break;

        case 'list_facilities':
            if (!table_exists('facility_records_facilities')) {
                json_response(['success' => false, 'message' => 'Facility tables are missing.'], 500);
            }
            $res = call_mysql_query("SELECT facility_id, facility_code, facility_name
                                     FROM facility_records_facilities
                                     WHERE status = 1
                                     ORDER BY facility_name ASC");
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'list_units':
            $facilityId = _int(_post('facility_id'));
            if ($facilityId <= 0) json_response(['success' => false, 'message' => 'Facility is required.'], 422);
            if (!table_exists('facility_records_units')) {
                json_response(['success' => false, 'message' => 'Facility unit table is missing.'], 500);
            }
            $hasUnitManager = table_column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = table_column_exists('facility_records_units', 'accountable_user_id');
            $unitManagerCol = $hasUnitManager ? 'facility_unit_manager_user_id' : ($hasUnitManagerLegacy ? 'accountable_user_id' : '');
            $res = call_mysql_query("SELECT
                                        u.unit_id,
                                        u.unit_code,
                                        u.unit_name,
                                        u.unit_type,
                                        " . ($unitManagerCol !== '' ? "u.{$unitManagerCol}" : "NULL") . " AS facility_unit_manager_user_id
                                     FROM facility_records_units u
                                     WHERE u.facility_id = {$facilityId} AND u.status = 1
                                     ORDER BY u.unit_name ASC");
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'list_users':
            $hasEmail = table_column_exists('users', 'email');
            $hasEmailAddress = table_column_exists('users', 'email_address');
            $hasRoleId = table_column_exists('users', 'role_id');
            $hasUserRole = table_column_exists('users', 'user_role');
            $emailExpr = $hasEmail ? "u.email" : ($hasEmailAddress ? "u.email_address" : "''");
            $roleExpr = $hasRoleId ? "u.role_id" : "NULL";
            $userRoleExpr = $hasUserRole ? "u.user_role" : "''";
            $res = call_mysql_query("SELECT
                                        u.user_id,
                                        {$emailExpr} AS email,
                                        u.username,
                                        {$roleExpr} AS role_id,
                                        {$userRoleExpr} AS user_role,
                                        CONCAT(COALESCE(u.l_name,''), ', ', COALESCE(u.f_name,''), IF(COALESCE(u.m_name,'') <> '', CONCAT(' ', LEFT(u.m_name,1), '.'), '')) AS full_name
                                     FROM users u
                                     WHERE " . active_user_where_clause() . "
                                     ORDER BY u.l_name ASC, u.f_name ASC
                                     LIMIT 500");
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'claim_requisition':
            $id = _int(_post('requisition_id'));
            $facilityId = _int(_post('facility_id'));
            $unitId = _int(_post('unit_id'));
            $issuedToUserId = _int(_post('issued_to_user_id'));
            $accountableUserId = _int(_post('accountable_user_id'));
            $managedByUserId = _int(_post('managed_by_user_id'));
            $remarks = _post('remarks');

            if ($id <= 0 || $facilityId <= 0 || $unitId <= 0) {
                json_response(['success' => false, 'message' => 'Requisition, facility, and unit are required.'], 422);
            }
            if (!table_exists('facility_records_assignments')) {
                json_response(['success' => false, 'message' => 'Facility assignment table is missing.'], 500);
            }

            $reqRes = call_mysql_query("SELECT * FROM requisition_items WHERE requisition_id = {$id} LIMIT 1");
            $req = $reqRes ? call_mysql_fetch_array($reqRes) : null;
            if (!$req) json_response(['success' => false, 'message' => 'Requisition not found.'], 404);

            $isAlreadyClaimed = false;
            if (table_column_exists('requisition_items', 'claim_assignment_id')) {
                $isAlreadyClaimed = (int)($req['claim_assignment_id'] ?? 0) > 0;
            } elseif (table_column_exists('requisition_items', 'claimed_at')) {
                $isAlreadyClaimed = !empty($req['claimed_at']);
            }
            if ($isAlreadyClaimed) {
                json_response(['success' => false, 'message' => 'Requisition is already claimed.'], 409);
            }
            $status = strtolower((string)($req['status'] ?? ''));
            if (!in_array($status, ['approved', 'reviewed'], true)) {
                json_response(['success' => false, 'message' => 'Requisition is not eligible for claim yet.'], 422);
            }

            $moduleType = strtoupper((string)($req['module_type'] ?? 'AST'));
            $itemCode = _esc((string)($req['item_code'] ?? ''));
            $qtyRequested = (int)($req['qty_requested'] ?? 0);
            if ($qtyRequested <= 0) $qtyRequested = 1;
            $itemDescription = (string)($req['item_description'] ?? '');
            $sourceItemId = 0;
            $itemUnit = '';

            if ($moduleType === 'AST') {
                $invRes = call_mysql_query("SELECT item_id, property_code, item_description, unit FROM ast_inventory WHERE property_code = '{$itemCode}' LIMIT 1");
                $inv = $invRes ? call_mysql_fetch_array($invRes) : null;
                if (!$inv) json_response(['success' => false, 'message' => 'AST inventory item not found.'], 404);
                $sourceItemId = (int)$inv['item_id'];
                $itemDescription = $inv['item_description'] ?: $itemDescription;
                $itemUnit = (string)($inv['unit'] ?? '');
                $qtyRequested = 1;
            } else {
                $moduleType = 'CSM';
                $invRes = call_mysql_query("SELECT inventory_id, inventory_system_item_code, item_description, item_name, current_unit_quantity
                                            FROM csm_inventory
                                            WHERE inventory_system_item_code = '{$itemCode}' LIMIT 1");
                $inv = $invRes ? call_mysql_fetch_array($invRes) : null;
                if (!$inv) json_response(['success' => false, 'message' => 'CSM inventory item not found.'], 404);
                $available = (int)($inv['current_unit_quantity'] ?? 0);
                if ($available < $qtyRequested) {
                    json_response(['success' => false, 'message' => 'CSM quantity is not enough for claim.'], 422);
                }
                $sourceItemId = (int)$inv['inventory_id'];
                $itemDescription = $inv['item_description'] ?: ($inv['item_name'] ?? $itemDescription);
                $itemUnit = '';
                call_mysql_query("UPDATE csm_inventory
                                  SET current_unit_quantity = current_unit_quantity - {$qtyRequested}
                                  WHERE inventory_id = {$sourceItemId}
                                  LIMIT 1");
            }

            $hasManagedBy = table_column_exists('facility_records_assignments', 'managed_by_user_id');
            $hasRequisitionId = table_column_exists('facility_records_assignments', 'requisition_id');

            $insCols = "facility_id, unit_id, module_type, source_item_id";
            $insVals = "{$facilityId}, {$unitId}, '" . _esc($moduleType) . "', {$sourceItemId}";
            if ($hasRequisitionId) {
                $insCols .= ", requisition_id";
                $insVals .= ", {$id}";
            }
            $insCols .= ", item_code, item_description, qty, unit, issued_to_user_id, accountable_user_id";
            $insVals .= ", '{$itemCode}', '" . _esc($itemDescription) . "', {$qtyRequested}, '" . _esc($itemUnit) . "', " . ($issuedToUserId > 0 ? $issuedToUserId : "NULL") . ", " . ($accountableUserId > 0 ? $accountableUserId : "NULL");
            if ($hasManagedBy) {
                $insCols .= ", managed_by_user_id";
                $insVals .= ", " . ($managedByUserId > 0 ? $managedByUserId : "NULL");
            }
            $insCols .= ", status, issued_at, remarks, created_by, updated_by";
            $insVals .= ", 'ACTIVE', NOW(), " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", " . (int)$s_user_id . ", " . (int)$s_user_id;
            $insSql = "INSERT INTO facility_records_assignments ({$insCols}) VALUES ({$insVals})";
            $okAssign = call_mysql_query($insSql);
            if (!$okAssign) json_response(['success' => false, 'message' => 'Failed to create facility assignment.'], 500);
            $assignmentId = mysqli_insert_id($db_connect);

            if (table_exists('facility_records_history')) {
                call_mysql_query("INSERT INTO facility_records_history
                                  (assignment_id, action, old_status, new_status, remarks, actor_user_id)
                                  VALUES
                                  ({$assignmentId}, 'CLAIMED_FROM_REQUISITION', NULL, 'ACTIVE', " . ($remarks !== '' ? "'" . _esc($remarks) . "'" : "NULL") . ", " . (int)$s_user_id . ")");
            }

            $reqSet = "updated_at = NOW()";
            if (table_column_exists('requisition_items', 'claim_assignment_id')) {
                $reqSet .= ", claim_assignment_id = {$assignmentId}";
            }
            if (table_column_exists('requisition_items', 'claimed_by_user_id')) {
                $reqSet .= ", claimed_by_user_id = " . (int)$s_user_id;
            }
            if (table_column_exists('requisition_items', 'claimed_at')) {
                $reqSet .= ", claimed_at = NOW()";
            }
            if (table_column_exists('requisition_items', 'claim_facility_id')) {
                $reqSet .= ", claim_facility_id = {$facilityId}";
            }
            if (table_column_exists('requisition_items', 'claim_unit_id')) {
                $reqSet .= ", claim_unit_id = {$unitId}";
            }
            $reqSet .= ", status = 'approved'";
            $okReq = call_mysql_query("UPDATE requisition_items SET {$reqSet} WHERE requisition_id = {$id} LIMIT 1");
            if (!$okReq) json_response(['success' => false, 'message' => 'Claim saved but failed to update requisition state.'], 500);

            activity_log_new("REQUISITION CLAIM", "SUCCESS", array(
                'requisition_id' => $id,
                'assignment_id' => $assignmentId,
                'facility_id' => $facilityId,
                'unit_id' => $unitId,
                'module_type' => $moduleType,
                'item_code' => $req['item_code'] ?? '',
                'qty_requested' => $qtyRequested,
                'issued_to_user_id' => safe_int($issuedToUserId),
                'accountable_user_id' => safe_int($accountableUserId),
                'managed_by_user_id' => safe_int($managedByUserId)
            ));
            json_response(['success' => true, 'message' => 'Requisition claimed and assigned to facility unit.']);
            break;

        case 'disapprove_requisition':
            $id = _int(_post('requisition_id'));
            $reason = _post('reason');
            if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid requisition.'], 422);
            $reqRes = call_mysql_query("SELECT * FROM requisition_items WHERE requisition_id = {$id} LIMIT 1");
            $req = $reqRes ? call_mysql_fetch_array($reqRes) : null;
            $prevStatus = $req['status'] ?? '';
            $ok = call_mysql_query("UPDATE requisition_items SET status='disapproved', reason='" . _esc($reason) . "', updated_at = NOW() WHERE requisition_id = {$id} LIMIT 1");
            if (!$ok) json_response(['success' => false, 'message' => 'Failed to disapprove requisition.'], 500);
            activity_log_new("REQUISITION DISAPPROVAL", "SUCCESS", array(
                'requisition_id' => $id,
                'module_type' => $req['module_type'] ?? '',
                'item_code' => $req['item_code'] ?? '',
                'item_description' => $req['item_description'] ?? '',
                'qty_requested' => (int)($req['qty_requested'] ?? 0),
                'requester_user_id' => (int)($req['requester_user_id'] ?? 0),
                'reason' => $reason,
                'old_status' => $prevStatus,
                'new_status' => 'disapproved'
            ));
            json_response(['success' => true, 'message' => 'Requisition disapproved.']);
            break;

        default:
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Server error occurred.'], 500);
}
