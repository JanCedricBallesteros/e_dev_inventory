<?php
require_once dirname(__DIR__) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

if (!(role_has("USER") || role_has("USERS"))) {
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
function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function get_employment_status_map() {
    $map = [];
    $res = call_mysql_query("SELECT employment_status_id, status_name, status_code FROM employment_status ORDER BY employment_status_id ASC");
    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            $map[(int)$row['employment_status_id']] = $row['status_name'] ?: ($row['status_code'] ?? '');
        }
    }
    return $map;
}

function normalize_position_category($position) {
    $p = strtolower(trim((string)$position));
    if ($p === '') return '';
    if (strpos($p, 'non') !== false && strpos($p, 'teaching') !== false) return 'non_teaching';
    if (strpos($p, 'non-teaching') !== false || strpos($p, 'non teaching') !== false || strpos($p, 'nonteaching') !== false) return 'non_teaching';
    if (strpos($p, 'teaching') !== false) return 'teaching';
    return '';
}

function get_current_user_position_category() {
    global $s_user_id;
    $userId = (int)$s_user_id;
    if ($userId <= 0) return '';
    $res = call_mysql_query("SELECT position FROM users WHERE user_id = {$userId} LIMIT 1");
    if ($res && ($row = call_mysql_fetch_array($res))) {
        return normalize_position_category($row['position'] ?? '');
    }
    return '';
}

function normalize_allowed_employment($raw) {
    if ($raw === null || $raw === '') {
        return [
            'mode' => 'all',
            'teaching' => [],
            'non_teaching' => []
        ];
    }
    $decoded = is_array($raw) ? $raw : json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        return [
            'mode' => 'all',
            'teaching' => [],
            'non_teaching' => []
        ];
    }
    if (array_key_exists('teaching', $decoded) || array_key_exists('non_teaching', $decoded) || array_key_exists('none', $decoded)) {
        $teaching = array_values(array_filter(array_map('intval', (array)($decoded['teaching'] ?? []))));
        $non = array_values(array_filter(array_map('intval', (array)($decoded['non_teaching'] ?? []))));
        $none = !empty($decoded['none']);
        if ($none) {
            return ['mode' => 'none', 'teaching' => [], 'non_teaching' => []];
        }
        if (empty($teaching) && empty($non)) {
            return ['mode' => 'none', 'teaching' => [], 'non_teaching' => []];
        }
        return ['mode' => 'structured', 'teaching' => $teaching, 'non_teaching' => $non];
    }
    $list = array_values($decoded);
    $listStr = array_map('strval', $list);
    if (in_array('NONE', $listStr, true)) {
        return ['mode' => 'none', 'teaching' => [], 'non_teaching' => []];
    }
    $ids = array_values(array_filter(array_map('intval', $list)));
    if (empty($ids)) {
        return ['mode' => 'all', 'teaching' => [], 'non_teaching' => []];
    }
    return ['mode' => 'legacy', 'teaching' => $ids, 'non_teaching' => $ids];
}

function build_allowed_status_label($norm, $statusMap) {
    $mode = $norm['mode'] ?? 'all';
    if ($mode === 'none') return 'None';
    if ($mode === 'all') return 'All';
    $teach = $norm['teaching'] ?? [];
    $non = $norm['non_teaching'] ?? [];
    $labels = [];
    if (!empty($teach)) {
        $names = [];
        foreach ($teach as $sid) {
            $sid = (int)$sid;
            if (isset($statusMap[$sid])) $names[] = $statusMap[$sid];
        }
        $labels[] = 'Teaching: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Teaching: None';
    }
    if (!empty($non)) {
        $names = [];
        foreach ($non as $sid) {
            $sid = (int)$sid;
            if (isset($statusMap[$sid])) $names[] = $statusMap[$sid];
        }
        $labels[] = 'Non-Teaching: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Non-Teaching: None';
    }
    return implode(' | ', $labels);
}

function get_current_user_status_id() {
    global $s_user_id;
    $userId = (int)$s_user_id;
    if ($userId <= 0) return 0;
    $res = call_mysql_query("SELECT employment_status_id FROM users WHERE user_id = {$userId} LIMIT 1");
    if ($res && ($row = call_mysql_fetch_array($res))) {
        return (int)($row['employment_status_id'] ?? 0);
    }
    return 0;
}

$action = _post('action', '');
if ($action === '') {
    json_response(['success' => false, 'message' => 'Missing action.'], 400);
}

try {
    switch ($action) {
        case 'list_available_items':
            $type = strtoupper(_post('type', 'AST'));
            $search = _post('search', '');
            $statusId = get_current_user_status_id();
            $statusMap = get_employment_status_map();
            $positionCategory = get_current_user_position_category();

            if (!in_array($type, ['AST', 'CSM'], true)) $type = 'AST';

            if ($type === 'AST') {
                $where = "WHERE i.is_available = 1 AND i.available_qty > 0";
                if ($search !== '') {
                    $searchEsc = _esc('%' . $search . '%');
                    $where .= " AND (i.property_code LIKE '{$searchEsc}' OR i.item_description LIKE '{$searchEsc}')";
                }

                $sql = "SELECT i.property_code, i.item_description, i.available_qty, i.unit, i.allowed_employment_status
                        FROM ast_inventory i
                        {$where}
                        ORDER BY i.created_at DESC";
                $res = call_mysql_query($sql);
                if ($res === false) {
                    json_response(['success' => false, 'message' => 'AST inventory table not found or query failed.'], 200);
                }

                $rows = [];
                while ($row = call_mysql_fetch_array($res)) {
                    $norm = normalize_allowed_employment($row['allowed_employment_status'] ?? '');
                    if ($norm['mode'] === 'none') {
                        continue;
                    }
                    if ($norm['mode'] !== 'all') {
                        if ($statusId <= 0) {
                            continue;
                        }
                        if ($norm['mode'] === 'legacy') {
                            if (!in_array($statusId, $norm['teaching'], true)) {
                                continue;
                            }
                        } else {
                            if ($positionCategory === 'teaching') {
                                if (!in_array($statusId, $norm['teaching'], true)) {
                                    continue;
                                }
                            } elseif ($positionCategory === 'non_teaching') {
                                if (!in_array($statusId, $norm['non_teaching'], true)) {
                                    continue;
                                }
                            } else {
                                continue;
                            }
                        }
                    }

                    $row['allowed_status_names'] = build_allowed_status_label($norm, $statusMap);

                    $rows[] = [
                        'item_code' => $row['property_code'],
                        'item_description' => $row['item_description'],
                        'available_qty' => (int)($row['available_qty'] ?? 0),
                        'unit' => $row['unit'] ?? '',
                        'allowed_status_names' => $row['allowed_status_names']
                    ];
                }
                json_response(['success' => true, 'data' => $rows]);
            } else {
                // CSM placeholder: list basic available items by current quantity
                $where = "WHERE c.status = 'available' AND c.current_unit_quantity > 0";
                if ($search !== '') {
                    $searchEsc = _esc('%' . $search . '%');
                    $where .= " AND (c.inventory_system_item_code LIKE '{$searchEsc}' OR c.item_name LIKE '{$searchEsc}' OR c.item_description LIKE '{$searchEsc}')";
                }
                $sql = "SELECT c.inventory_system_item_code, c.item_name, c.item_description, c.current_unit_quantity
                        FROM csm_inventory c
                        {$where}
                        ORDER BY c.created_at DESC";
                $res = call_mysql_query($sql);
                if ($res === false) {
                    json_response(['success' => false, 'message' => 'CSM inventory table not found or query failed.'], 200);
                }
                $rows = [];
                while ($row = call_mysql_fetch_array($res)) {
                    $desc = $row['item_description'] ?: $row['item_name'];
                    $rows[] = [
                        'item_code' => $row['inventory_system_item_code'],
                        'item_description' => $desc,
                        'available_qty' => (int)($row['current_unit_quantity'] ?? 0),
                        'unit' => '',
                        'allowed_status_names' => 'All'
                    ];
                }
                json_response(['success' => true, 'data' => $rows]);
            }
            break;

        case 'create_request':
            $type = strtoupper(_post('type', 'AST'));
            $itemCode = _post('item_code');
            $qty = _int(_post('qty_requested'), 0);
            if (!in_array($type, ['AST', 'CSM'], true)) $type = 'AST';
            if ($itemCode === '') json_response(['success' => false, 'message' => 'Item code is required.'], 422);
            if ($qty <= 0) json_response(['success' => false, 'message' => 'Invalid quantity.'], 422);

            $userId = (int)$s_user_id;
            if ($userId <= 0) json_response(['success' => false, 'message' => 'Invalid user.'], 403);

            if ($type === 'AST') {
                if ($qty !== 1) {
                    json_response(['success' => false, 'message' => 'AST requests must be exactly 1 per property code.'], 422);
                }
                $itemCodeEsc = _esc($itemCode);
                $invRes = call_mysql_query("SELECT property_code, item_description, available_qty, is_available, allowed_employment_status 
                                            FROM ast_inventory WHERE property_code = '{$itemCodeEsc}' LIMIT 1");
                $invRow = $invRes ? call_mysql_fetch_array($invRes) : null;
                if (!$invRow) json_response(['success' => false, 'message' => 'Item not found.'], 404);

                $availableQty = (int)($invRow['available_qty'] ?? 0);
                if ((int)$invRow['is_available'] !== 1 || $availableQty <= 0) {
                    json_response(['success' => false, 'message' => 'Item is not available for requisition.'], 422);
                }
                if ($qty > $availableQty) {
                    json_response(['success' => false, 'message' => 'Requested quantity exceeds available.'], 422);
                }

                $norm = normalize_allowed_employment($invRow['allowed_employment_status'] ?? '');
                if ($norm['mode'] === 'none') {
                    json_response(['success' => false, 'message' => 'Item is not available for your position/status.'], 422);
                }
                if ($norm['mode'] !== 'all') {
                    $statusId = get_current_user_status_id();
                    $posCat = get_current_user_position_category();
                    if ($statusId <= 0) {
                        json_response(['success' => false, 'message' => 'Your employment status is not allowed for this item.'], 422);
                    }
                    if ($norm['mode'] === 'legacy') {
                        if (!in_array($statusId, $norm['teaching'], true)) {
                            json_response(['success' => false, 'message' => 'Your employment status is not allowed for this item.'], 422);
                        }
                    } else {
                        if ($posCat === 'teaching') {
                            if (!in_array($statusId, $norm['teaching'], true)) {
                                json_response(['success' => false, 'message' => 'Your employment status is not allowed for this item.'], 422);
                            }
                        } elseif ($posCat === 'non_teaching') {
                            if (!in_array($statusId, $norm['non_teaching'], true)) {
                                json_response(['success' => false, 'message' => 'Your employment status is not allowed for this item.'], 422);
                            }
                        } else {
                            json_response(['success' => false, 'message' => 'Your position is not allowed for this item.'], 422);
                        }
                    }
                }

                $descEsc = _esc($invRow['item_description'] ?? '');
                $sql = "INSERT INTO requisition_items 
                        (module_type, item_code, item_description, qty_requested, requester_user_id, status, created_at, updated_at)
                        VALUES 
                        ('AST', '{$itemCodeEsc}', '{$descEsc}', {$qty}, {$userId}, 'pending', NOW(), NOW())";
                $ok = call_mysql_query($sql);
                if (!$ok) {
                    json_response(['success' => false, 'message' => 'Failed to submit request. Ensure requisition table exists.'], 500);
                }
                json_response(['success' => true, 'message' => 'Request submitted.']);
            } else {
                $itemCodeEsc = _esc($itemCode);
                $invRes = call_mysql_query("SELECT inventory_system_item_code, item_name, item_description, current_unit_quantity, status 
                                            FROM csm_inventory WHERE inventory_system_item_code = '{$itemCodeEsc}' LIMIT 1");
                $invRow = $invRes ? call_mysql_fetch_array($invRes) : null;
                if (!$invRow) json_response(['success' => false, 'message' => 'Item not found.'], 404);
                if ($invRow['status'] !== 'available') {
                    json_response(['success' => false, 'message' => 'Item is not available for requisition.'], 422);
                }
                $availableQty = (int)($invRow['current_unit_quantity'] ?? 0);
                if ($qty > $availableQty) {
                    json_response(['success' => false, 'message' => 'Requested quantity exceeds available.'], 422);
                }

                $desc = $invRow['item_description'] ?: $invRow['item_name'];
                $descEsc = _esc($desc);
                $sql = "INSERT INTO requisition_items 
                        (module_type, item_code, item_description, qty_requested, requester_user_id, status, created_at, updated_at)
                        VALUES 
                        ('CSM', '{$itemCodeEsc}', '{$descEsc}', {$qty}, {$userId}, 'pending', NOW(), NOW())";
                $ok = call_mysql_query($sql);
                if (!$ok) {
                    json_response(['success' => false, 'message' => 'Failed to submit request. Ensure requisition table exists.'], 500);
                }
                json_response(['success' => true, 'message' => 'Request submitted.']);
            }
            break;

        default:
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Server error occurred.'], 500);
}
