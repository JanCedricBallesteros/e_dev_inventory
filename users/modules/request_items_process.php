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
function table_exists($table) {
    $res = call_mysql_query("SHOW TABLES LIKE '" . _esc($table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}
function table_column_exists($table, $column) {
    $res = call_mysql_query("SHOW COLUMNS FROM `" . _esc($table) . "` LIKE '" . _esc($column) . "'");
    return $res && mysqli_num_rows($res) > 0;
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
        $labels[] = 'Academic Personnel: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Academic Personnel: None';
    }
    if (!empty($non)) {
        $names = [];
        foreach ($non as $sid) {
            $sid = (int)$sid;
            if (isset($statusMap[$sid])) $names[] = $statusMap[$sid];
        }
        $labels[] = 'Administrative: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Administrative: None';
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

function is_allowed_for_user($norm, $statusId, $positionCategory) {
    $mode = $norm['mode'] ?? 'all';
    if ($mode === 'none') return false;
    if ($mode === 'all') return true;
    if ($statusId <= 0) return false;

    $teaching = array_values(array_filter(array_map('intval', (array)($norm['teaching'] ?? []))));
    $nonTeaching = array_values(array_filter(array_map('intval', (array)($norm['non_teaching'] ?? []))));

    if ($mode === 'legacy') {
        return in_array((int)$statusId, $teaching, true);
    }

    $pos = strtolower(trim((string)$positionCategory));
    if ($pos === 'teaching') {
        return in_array((int)$statusId, $teaching, true);
    }
    if ($pos === 'non_teaching') {
        return in_array((int)$statusId, $nonTeaching, true);
    }

    // Fallback: if position category is unknown/unmapped, allow if status matches either bucket.
    return in_array((int)$statusId, $teaching, true) || in_array((int)$statusId, $nonTeaching, true);
}

function requisition_is_claimed($row, $hasClaimAssignment, $hasClaimedAt, $hasReqIdInAssignments) {
    $claimAssignmentId = (int)($row['claim_assignment_id'] ?? 0);
    if ($hasClaimAssignment && $claimAssignmentId > 0) return true;
    if ($hasClaimedAt && !empty($row['claimed_at'])) return true;
    if ($hasReqIdInAssignments) {
        $reqId = (int)($row['requisition_id'] ?? 0);
        if ($reqId > 0) {
            $res = call_mysql_query("SELECT assignment_id
                                     FROM facility_records_assignments
                                     WHERE requisition_id = {$reqId}
                                       AND status <> 'RETURNED'
                                     LIMIT 1");
            if ($res && call_mysql_fetch_array($res)) return true;
        }
    }
    return false;
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
                $where = "WHERE i.is_available = 1";
                if ($search !== '') {
                    $searchEsc = _esc('%' . $search . '%');
                    $where .= " AND (i.property_code LIKE '{$searchEsc}' OR i.item_description LIKE '{$searchEsc}')";
                }

                $sql = "SELECT i.property_code, i.item_description, i.unit, i.allowed_employment_status,
                               ast_cat.category_photo AS ast_category_photo,
                               ast_cat.item_category_name AS ast_category_name
                        FROM ast_inventory i
                        LEFT JOIN ast_inventory_category ast_cat ON ast_cat.category_id = i.category_id
                        {$where}
                        ORDER BY i.created_at DESC";
                $res = call_mysql_query($sql);
                if ($res === false) {
                    json_response(['success' => false, 'message' => 'AST inventory table not found or query failed.'], 200);
                }

                $rows = [];
                while ($row = call_mysql_fetch_array($res)) {
                    $norm = normalize_allowed_employment($row['allowed_employment_status'] ?? '');
                    if (!is_allowed_for_user($norm, (int)$statusId, (string)$positionCategory)) {
                        continue;
                    }

                    $row['allowed_status_names'] = build_allowed_status_label($norm, $statusMap);
                    $astPhoto = !empty($row['ast_category_photo']) ? $row['ast_category_photo'] : '';
                    $rows[] = [
                        'item_code' => $row['property_code'],
                        'item_description' => $row['item_description'],
                        'unit' => $row['unit'] ?? '',
                        'allowed_status_names' => $row['allowed_status_names'],
                        'item_category_name' => $row['ast_category_name'] ?? '',
                        'category_photo_url' => $astPhoto ? BASE_URL . 'upload/category/' . $astPhoto : null,
                        'category_photo_thumb_url' => $astPhoto ? BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($astPhoto) . '&s=100' : null,
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
                $sql = "SELECT c.inventory_system_item_code, c.item_name, c.item_description, c.current_unit_quantity,
                               c.item_category_img AS csm_category_img,
                               csm_cat.item_category_name AS csm_category_name
                        FROM csm_inventory c
                        LEFT JOIN csm_inventory_category csm_cat ON csm_cat.item_category_code = c.item_category_code
                        {$where}
                        ORDER BY c.created_at DESC";
                $res = call_mysql_query($sql);
                if ($res === false) {
                    json_response(['success' => false, 'message' => 'CSM inventory table not found or query failed.'], 200);
                }
                $rows = [];
                while ($row = call_mysql_fetch_array($res)) {
                    $desc = $row['item_description'] ?: $row['item_name'];
                    $csmImg = !empty($row['csm_category_img']) ? $row['csm_category_img'] : '';
                    $csmPhotoUrl = null;
                    $csmThumbUrl = null;
                    if ($csmImg) {
                        if (strpos($csmImg, 'upload/') === 0 || strpos($csmImg, '/') !== false) {
                            $csmPhotoUrl = BASE_URL . $csmImg;
                            $csmThumbUrl = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode(basename($csmImg)) . '&s=100';
                        } else {
                            $csmPhotoUrl = BASE_URL . 'upload/category/' . $csmImg;
                            $csmThumbUrl = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($csmImg) . '&s=100';
                        }
                    }
                    $rows[] = [
                        'item_code' => $row['inventory_system_item_code'],
                        'item_description' => $desc,
                        'available_qty' => (int)($row['current_unit_quantity'] ?? 0),
                        'unit' => '',
                        'allowed_status_names' => 'All',
                        'item_category_name' => $row['csm_category_name'] ?? '',
                        'category_photo_url' => $csmPhotoUrl,
                        'category_photo_thumb_url' => $csmThumbUrl,
                    ];
                }
                json_response(['success' => true, 'data' => $rows]);
            }
            break;

        case 'list_facilities':
            if (!table_exists('facility_records_facilities')) {
                json_response(['success' => false, 'message' => 'Facility tables are missing.'], 500);
            }
            $res = call_mysql_query("SELECT facility_id, facility_code, facility_name
                                     FROM facility_records_facilities
                                     WHERE status = 1
                                       AND UPPER(TRIM(COALESCE(facility_code, ''))) <> 'STOCKROOM'
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
            if (table_exists('facility_records_facilities')) {
                $chk = call_mysql_query("SELECT facility_id
                                         FROM facility_records_facilities
                                         WHERE facility_id = {$facilityId}
                                           AND UPPER(TRIM(COALESCE(facility_code, ''))) = 'STOCKROOM'
                                         LIMIT 1");
                if ($chk && call_mysql_fetch_array($chk)) {
                    json_response(['success' => true, 'data' => []]);
                }
            }
            $res = call_mysql_query("SELECT unit_id, unit_code, unit_name
                                     FROM facility_records_units
                                     WHERE facility_id = {$facilityId} AND status = 1
                                     ORDER BY unit_name ASC");
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'create_request':
            $type = strtoupper(_post('type', 'AST'));
            $itemCode = _post('item_code');
            $qty = _int(_post('qty_requested'), 0);
            $facilityId = _int(_post('facility_id'), 0);
            $unitId = _int(_post('unit_id'), 0);
            if (!in_array($type, ['AST', 'CSM'], true)) $type = 'AST';
            if ($itemCode === '') json_response(['success' => false, 'message' => 'Item code is required.'], 422);
            if ($qty <= 0) json_response(['success' => false, 'message' => 'Invalid quantity.'], 422);
            if ($facilityId <= 0 || $unitId <= 0) {
                json_response(['success' => false, 'message' => 'Facility and unit are required.'], 422);
            }

            $userId = (int)$s_user_id;
            if ($userId <= 0) json_response(['success' => false, 'message' => 'Invalid user.'], 403);
            if (!table_exists('facility_records_units')) {
                json_response(['success' => false, 'message' => 'Facility tables are missing.'], 500);
            }
            $unitRes = call_mysql_query("SELECT unit_id FROM facility_records_units WHERE unit_id = {$unitId} AND facility_id = {$facilityId} LIMIT 1");
            if (!$unitRes || !call_mysql_fetch_array($unitRes)) {
                json_response(['success' => false, 'message' => 'Selected unit does not belong to the facility.'], 422);
            }

            if ($type === 'AST') {
                if ($qty !== 1) {
                    json_response(['success' => false, 'message' => 'AST requests must be exactly 1 per Property Tag.'], 422);
                }
                $itemCodeEsc = _esc($itemCode);
                $invRes = call_mysql_query("SELECT property_code, item_description, is_available, allowed_employment_status 
                                            FROM ast_inventory WHERE property_code = '{$itemCodeEsc}' LIMIT 1");
                $invRow = $invRes ? call_mysql_fetch_array($invRes) : null;
                if (!$invRow) json_response(['success' => false, 'message' => 'Item not found.'], 404);

                if ((int)$invRow['is_available'] !== 1) {
                    json_response(['success' => false, 'message' => 'Item is not available for requisition.'], 422);
                }

                $norm = normalize_allowed_employment($invRow['allowed_employment_status'] ?? '');
                $statusId = get_current_user_status_id();
                $posCat = get_current_user_position_category();
                if (!is_allowed_for_user($norm, (int)$statusId, (string)$posCat)) {
                    json_response(['success' => false, 'message' => 'Item is not available for your current employment settings.'], 422);
                }

                $descEsc = _esc($invRow['item_description'] ?? '');
                $cols = "module_type, item_code, item_description, qty_requested, requester_user_id, status, created_at, updated_at";
                $vals = "'AST', '{$itemCodeEsc}', '{$descEsc}', {$qty}, {$userId}, 'pending', NOW(), NOW()";
                if (table_column_exists('requisition_items', 'claim_facility_id')) {
                    $cols .= ", claim_facility_id";
                    $vals .= ", {$facilityId}";
                }
                if (table_column_exists('requisition_items', 'claim_unit_id')) {
                    $cols .= ", claim_unit_id";
                    $vals .= ", {$unitId}";
                }
                $sql = "INSERT INTO requisition_items ({$cols}) VALUES ({$vals})";
                $ok = call_mysql_query($sql);
                if (!$ok) {
                    json_response(['success' => false, 'message' => 'Failed to submit request. Ensure requisition table exists.'], 500);
                }
                activity_log_new("USER REQUISITION SUBMIT", "SUCCESS", array(
                    "module_type" => "AST",
                    "item_code" => $itemCode,
                    "qty_requested" => $qty,
                    "claim_facility_id" => $facilityId,
                    "claim_unit_id" => $unitId
                ));
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
                $cols = "module_type, item_code, item_description, qty_requested, requester_user_id, status, created_at, updated_at";
                $vals = "'CSM', '{$itemCodeEsc}', '{$descEsc}', {$qty}, {$userId}, 'pending', NOW(), NOW()";
                if (table_column_exists('requisition_items', 'claim_facility_id')) {
                    $cols .= ", claim_facility_id";
                    $vals .= ", {$facilityId}";
                }
                if (table_column_exists('requisition_items', 'claim_unit_id')) {
                    $cols .= ", claim_unit_id";
                    $vals .= ", {$unitId}";
                }
                $sql = "INSERT INTO requisition_items ({$cols}) VALUES ({$vals})";
                $ok = call_mysql_query($sql);
                if (!$ok) {
                    json_response(['success' => false, 'message' => 'Failed to submit request. Ensure requisition table exists.'], 500);
                }
                activity_log_new("USER REQUISITION SUBMIT", "SUCCESS", array(
                    "module_type" => "CSM",
                    "item_code" => $itemCode,
                    "qty_requested" => $qty,
                    "claim_facility_id" => $facilityId,
                    "claim_unit_id" => $unitId
                ));
                json_response(['success' => true, 'message' => 'Request submitted.']);
            }
            break;

        case 'list_my_requisitions':
            $userId = (int)$s_user_id;
            if ($userId <= 0) json_response(['success' => false, 'message' => 'Invalid user.'], 403);
            if (!table_exists('requisition_items')) {
                json_response(['success' => false, 'message' => 'Requisition table not found.'], 500);
            }
            $status = strtolower(_post('status'));
            $search = _post('search');
            $hasClaimAssignment = table_column_exists('requisition_items', 'claim_assignment_id');
            $hasClaimedAt = table_column_exists('requisition_items', 'claimed_at');
            $hasApprovedAt = table_column_exists('requisition_items', 'approved_at');
            $hasUpdatedAt = table_column_exists('requisition_items', 'updated_at');
            $hasReason = table_column_exists('requisition_items', 'reason');
            $hasRemarks = table_column_exists('requisition_items', 'remarks');
            $hasReqIdInAssignments = table_column_exists('facility_records_assignments', 'requisition_id');
            $assignJoin = '';
            if ($hasClaimAssignment) {
                $assignJoin = "LEFT JOIN facility_records_assignments a ON a.assignment_id = r.claim_assignment_id";
            } elseif ($hasReqIdInAssignments) {
                $assignJoin = "LEFT JOIN facility_records_assignments a ON a.requisition_id = r.requisition_id";
            }
            $where = "WHERE r.requester_user_id = {$userId}";
            if ($status !== '') {
                if ($status === 'for_claiming') {
                    $where .= " AND r.status = 'approved'";
                    if ($hasClaimAssignment) {
                        $where .= " AND r.claim_assignment_id IS NULL";
                    } elseif ($hasClaimedAt) {
                        $where .= " AND r.claimed_at IS NULL";
                    }
                } elseif ($status === 'claimed') {
                    if ($hasClaimAssignment) {
                        $where .= " AND r.claim_assignment_id IS NOT NULL";
                    } elseif ($hasClaimedAt) {
                        $where .= " AND r.claimed_at IS NOT NULL";
                    } elseif ($hasReqIdInAssignments) {
                        $where .= " AND EXISTS (
                                        SELECT 1
                                        FROM facility_records_assignments ax
                                        WHERE ax.requisition_id = r.requisition_id
                                          AND ax.status <> 'RETURNED'
                                    )";
                    } else {
                        $where .= " AND r.status = 'claimed'";
                    }
                } else {
                    $where .= " AND r.status = '" . _esc($status) . "'";
                }
            }
            if ($status === 'for_claiming' && !$hasClaimAssignment && !$hasClaimedAt && $hasReqIdInAssignments) {
                $where .= " AND NOT EXISTS (
                                SELECT 1
                                FROM facility_records_assignments ax
                                WHERE ax.requisition_id = r.requisition_id
                                  AND ax.status <> 'RETURNED'
                            )";
            }
            if ($search !== '') {
                $searchEsc = _esc('%' . $search . '%');
                $where .= " AND (r.item_code LIKE '{$searchEsc}' OR r.item_description LIKE '{$searchEsc}')";
            }
            $sql = "SELECT
                        r.requisition_id,
                        r.module_type,
                        r.item_code,
                        r.item_description,
                        r.qty_requested,
                        r.status,
                        " . ($assignJoin !== '' && $hasRemarks ? "COALESCE(a.remarks, r.remarks) AS remarks," : ($assignJoin !== '' ? "a.remarks AS remarks," : ($hasRemarks ? "r.remarks," : "NULL AS remarks,"))) . "
                        " . ($hasReason ? "r.reason," : "NULL AS reason,") . "
                        " . ($hasClaimAssignment ? "r.claim_assignment_id," : "NULL AS claim_assignment_id,") . "
                        " . ($hasClaimedAt ? "r.claimed_at," : "NULL AS claimed_at,") . "
                        " . ($hasApprovedAt ? "r.approved_at," : "NULL AS approved_at,") . "
                        r.created_at,
                        " . ($hasUpdatedAt ? "r.updated_at," : "r.created_at AS updated_at,") . "
                        f.facility_code,
                        f.facility_name,
                        u.unit_code,
                        u.unit_name,
                        ast_cat.category_photo AS ast_category_photo,
                        ast_cat.item_category_name AS ast_category_name,
                        csm_inv.item_category_img AS csm_category_img,
                        csm_cat.item_category_name AS csm_category_name
                    FROM requisition_items r
                    {$assignJoin}
                    LEFT JOIN facility_records_facilities f ON f.facility_id = a.facility_id
                    LEFT JOIN facility_records_units u ON u.unit_id = a.unit_id
                    LEFT JOIN ast_inventory ast_inv ON r.module_type = 'AST' AND r.item_code = ast_inv.property_code
                    LEFT JOIN ast_inventory_category ast_cat ON ast_inv.category_id = ast_cat.category_id
                    LEFT JOIN csm_inventory csm_inv ON r.module_type = 'CSM' AND r.item_code = csm_inv.inventory_system_item_code
                    LEFT JOIN csm_inventory_category csm_cat ON csm_inv.item_category_code = csm_cat.item_category_code
                    {$where}
                    ORDER BY r.created_at DESC";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    // Auto-expire AST approved requisitions not claimed within 7 days.
                    if (strtoupper((string)$row['module_type']) === 'AST' && $hasApprovedAt) {
                        $rawStatus = strtolower((string)($row['status'] ?? 'pending'));
                        $isClaimed = requisition_is_claimed($row, $hasClaimAssignment, $hasClaimedAt, $hasReqIdInAssignments);
                        $approvedAt = $row['approved_at'] ?? null;
                        if ($rawStatus === 'approved' && !$isClaimed && $approvedAt) {
                            $approvedTs = strtotime($approvedAt);
                            if ($approvedTs && (time() - $approvedTs) > (7 * 24 * 60 * 60)) {
                                $itemCodeEsc = _esc($row['item_code'] ?? '');
                                call_mysql_query("UPDATE requisition_items SET status = 'not_claimed', updated_at = NOW() WHERE requisition_id = " . (int)$row['requisition_id'] . " LIMIT 1");
                                call_mysql_query("UPDATE ast_inventory SET is_available = 1 WHERE property_code = '{$itemCodeEsc}' LIMIT 1");
                                activity_log_new("REQUISITION NOT CLAIMED", "SUCCESS", array(
                                    'requisition_id' => (int)$row['requisition_id'],
                                    'item_code' => $row['item_code'] ?? '',
                                    'module_type' => $row['module_type'] ?? ''
                                ));
                                $row['status'] = 'not_claimed';
                            }
                        }
                    }
                    $rawStatus = strtolower((string)($row['status'] ?? 'pending'));
                    $isClaimed = requisition_is_claimed($row, $hasClaimAssignment, $hasClaimedAt, $hasReqIdInAssignments);
                    if ($isClaimed || $rawStatus === 'claimed') {
                        $row['workflow_status'] = 'claimed';
                    } elseif ($rawStatus === 'approved') {
                        $row['workflow_status'] = 'for_claiming';
                    } elseif ($rawStatus === 'not_claimed') {
                        $row['workflow_status'] = 'not_claimed';
                    } else {
                        $row['workflow_status'] = $rawStatus;
                    }
                    // Resolve category images
                    $row['category_photo_url'] = null;
                    $row['category_photo_thumb_url'] = null;
                    $row['item_category_name'] = null;
                    $moduleType = strtoupper((string)($row['module_type'] ?? ''));
                    if ($moduleType === 'AST' && !empty($row['ast_category_photo'])) {
                        $row['item_category_name'] = $row['ast_category_name'] ?? null;
                        $row['category_photo_url'] = BASE_URL . 'upload/category/' . $row['ast_category_photo'];
                        $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($row['ast_category_photo']) . '&s=100';
                    } elseif ($moduleType === 'CSM') {
                        $row['item_category_name'] = $row['csm_category_name'] ?? null;
                        $csmImg = $row['csm_category_img'] ?? '';
                        if (!empty($csmImg)) {
                            if (strpos($csmImg, 'upload/') === 0 || strpos($csmImg, '/') !== false) {
                                $row['category_photo_url'] = BASE_URL . $csmImg;
                                $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode(basename($csmImg)) . '&s=100';
                            } else {
                                $row['category_photo_url'] = BASE_URL . 'upload/category/' . $csmImg;
                                $row['category_photo_thumb_url'] = BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($csmImg) . '&s=100';
                            }
                        }
                    }
                    unset($row['ast_category_photo'], $row['ast_category_name'], $row['csm_category_img'], $row['csm_category_name']);
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    call_mysql_query("ROLLBACK");
    json_response(['success' => false, 'message' => 'Server error occurred.'], 500);
}


