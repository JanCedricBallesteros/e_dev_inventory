<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$staffAccess = (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("PO", "AST", "CSM"));
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

            $sql = "SELECT 
                        r.requisition_id,
                        r.module_type,
                        r.item_code,
                        r.item_description,
                        r.qty_requested,
                        r.status,
                        r.created_at,
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

            $ok = call_mysql_query("UPDATE requisition_items SET status='approved', updated_at = NOW() WHERE requisition_id = {$id} LIMIT 1");
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
