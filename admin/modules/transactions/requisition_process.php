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

function requester_is_allowed_for_item($norm, $statusId, $positionCategory) {
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

    // Fallback: if requester position text is unmapped, allow by status in either bucket.
    return in_array((int)$statusId, $teaching, true) || in_array((int)$statusId, $nonTeaching, true);
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

function enum_values_from_mysql_type($typeDef) {
    $typeDef = trim((string)$typeDef);
    if (!preg_match('/^enum\((.*)\)$/i', $typeDef, $m)) {
        return array();
    }
    $inner = $m[1];
    $vals = str_getcsv($inner, ',', "'");
    return array_values(array_filter(array_map(function ($v) {
        return trim((string)$v);
    }, $vals), function ($v) {
        return $v !== '';
    }));
}

function ensure_requisition_status_enum_support() {
    if (!table_exists('requisition_items') || !table_column_exists('requisition_items', 'status')) {
        return;
    }

    $colRes = call_mysql_query("SHOW COLUMNS FROM requisition_items LIKE 'status'");
    $colRow = $colRes ? call_mysql_fetch_array($colRes) : null;
    if (!$colRow) return;

    $typeDef = (string)($colRow['Type'] ?? '');
    $currentVals = enum_values_from_mysql_type($typeDef);
    if (empty($currentVals)) return;

    $required = array('pending', 'reviewed', 'approved', 'disapproved', 'claimed', 'not_claimed');
    $missing = array_diff($required, $currentVals);

    if (!empty($missing)) {
        call_mysql_query("ALTER TABLE requisition_items
                         MODIFY COLUMN status ENUM('pending','reviewed','approved','disapproved','claimed','not_claimed')
                         NOT NULL DEFAULT 'pending'");
    }

    $hasUpdatedAt = table_column_exists('requisition_items', 'updated_at');
    $setClause = "status = 'not_claimed'" . ($hasUpdatedAt ? ", updated_at = NOW()" : "");
    call_mysql_query("UPDATE requisition_items
                     SET {$setClause}
                     WHERE status IS NULL OR TRIM(status) = ''");
}

function ensure_personal_use_location() {
    global $db_connect, $s_user_id;
    if (!table_exists('facility_records_facilities')) {
        return;
    }

    $actor = isset($s_user_id) ? (int)$s_user_id : 0;

    $facilityRes = call_mysql_query("SELECT facility_id
                                     FROM facility_records_facilities
                                     WHERE UPPER(TRIM(COALESCE(facility_code, ''))) = 'PERSONAL'
                                     ORDER BY facility_id ASC
                                     LIMIT 1");
    $facilityRow = $facilityRes ? call_mysql_fetch_array($facilityRes) : null;
    $facilityId = (int)($facilityRow['facility_id'] ?? 0);
    if ($facilityId <= 0) {
        $okFacility = call_mysql_query("INSERT INTO facility_records_facilities
                                        (facility_code, facility_name, facility_floor, status, created_by, updated_by)
                                        VALUES (
                                            'PERSONAL',
                                            'For Personal use',
                                            '" . _esc(json_encode(array('floors' => array()), JSON_UNESCAPED_UNICODE)) . "',
                                            1,
                                            " . ($actor > 0 ? $actor : "NULL") . ",
                                            " . ($actor > 0 ? $actor : "NULL") . "
                                        )");
        if (!$okFacility) return;
        $facilityId = (int)mysqli_insert_id($db_connect);
    }

    // Personal-use location intentionally has no units (stockroom-like behavior).
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

function can_access_module_type($type) {
    $type = strtoupper((string)$type);
    if (role_has("ADMIN")) return true;
    if (!(role_has("ADMIN_STAFF") || role_has("ADMINSTAFF"))) return false;
    return user_has_access($type);
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

ensure_personal_use_location();
ensure_requisition_status_enum_support();

try {
    switch ($action) {
        case 'list_requisitions':
            $type = strtoupper(_post('type'));
            $status = strtolower(_post('status'));
            $search = _post('search');
            $csmQtyColumn = '';
            if (table_column_exists('csm_inventory', 'current_unit_quantity')) {
                $csmQtyColumn = 'current_unit_quantity';
            } elseif (table_column_exists('csm_inventory', 'quantity')) {
                $csmQtyColumn = 'quantity';
            }
            $csmAvailableExpr = $csmQtyColumn !== '' ? "csm_inv.{$csmQtyColumn}" : "r.qty_requested";
            if (!in_array($type, ['AST', 'CSM'], true)) {
                $type = 'AST';
            }
            if (!can_access_module_type($type)) {
                json_response(['success' => false, 'message' => 'Access denied for this module.'], 403);
            }

            $where = "WHERE r.module_type = '" . _esc($type) . "'";
            $hasClaimAssignment = table_column_exists('requisition_items', 'claim_assignment_id');
            $hasClaimedAt = table_column_exists('requisition_items', 'claimed_at');
            $hasRemarks = table_column_exists('requisition_items', 'remarks');
            $hasReqIdInAssignments = table_column_exists('facility_records_assignments', 'requisition_id');
            $assignJoin = '';
            if ($hasClaimAssignment) {
                $assignJoin = "LEFT JOIN facility_records_assignments a ON a.assignment_id = r.claim_assignment_id";
            } elseif ($hasReqIdInAssignments) {
                $assignJoin = "LEFT JOIN facility_records_assignments a ON a.requisition_id = r.requisition_id";
            }
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
                $where .= " AND (r.item_code LIKE '{$searchEsc}' OR r.item_description LIKE '{$searchEsc}' OR u.f_name LIKE '{$searchEsc}' OR u.l_name LIKE '{$searchEsc}')";
            }

            $hasUpdatedAt = table_column_exists('requisition_items', 'updated_at');
            $hasApprovedAt = table_column_exists('requisition_items', 'approved_at');
            
            $sql = "SELECT 
                        r.requisition_id,
                        r.module_type,
                        r.item_code,
                        r.item_description,
                        r.qty_requested,
                        r.status,
                        " . ($assignJoin !== '' ? "a.remarks AS assignment_remarks," : "NULL AS assignment_remarks,") . "
                        " . ($assignJoin !== '' && $hasRemarks ? "COALESCE(a.remarks, r.remarks) AS remarks," : ($assignJoin !== '' ? "a.remarks AS remarks," : ($hasRemarks ? "r.remarks," : "NULL AS remarks,"))) . "
                        " . ($hasClaimAssignment ? "r.claim_assignment_id," : "NULL AS claim_assignment_id,") . "
                        " . ($hasClaimedAt ? "r.claimed_at," : "NULL AS claimed_at,") . "
                        " . (table_column_exists('requisition_items', 'claim_facility_id') ? "r.claim_facility_id," : "NULL AS claim_facility_id,") . "
                        " . (table_column_exists('requisition_items', 'claim_unit_id') ? "r.claim_unit_id," : "NULL AS claim_unit_id,") . "
                        r.created_at,
                        " . ($hasUpdatedAt ? "r.updated_at," : "r.created_at AS updated_at,") . "
                        " . ($hasApprovedAt ? "r.approved_at," : "NULL AS approved_at,") . "
                        r.requester_user_id,
                        u.user_id,
                        u.f_name,
                        u.m_name,
                        u.l_name,
                        u.suffix,
                        u.employment_status_id,
                        u.position,
                        es.status_name AS employment_status,
                        ast_cat.category_photo AS ast_category_photo,
                        ast_cat.item_category_name AS ast_category_name,
                        {$csmAvailableExpr} AS csm_available_qty,
                        csm_inv.item_category_img AS csm_category_img,
                        csm_cat.item_category_name AS csm_category_name
                    FROM requisition_items r
                    {$assignJoin}
                    LEFT JOIN ast_inventory ast_inv ON r.module_type = 'AST' AND r.item_code = ast_inv.property_code
                    LEFT JOIN ast_inventory_category ast_cat ON ast_inv.category_id = ast_cat.category_id
                    LEFT JOIN csm_inventory csm_inv ON r.module_type = 'CSM' AND r.item_code = csm_inv.inventory_system_item_code
                    LEFT JOIN csm_inventory_category csm_cat ON csm_inv.item_category_code = csm_cat.item_category_code
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
                // Auto-expire AST approved requisitions that were not claimed within 7 days.
                if ($type === 'AST' && $hasApprovedAt) {
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
                $row['requester_name'] = get_full_name($row['f_name'], $row['m_name'], $row['l_name'], $row['suffix']);
                $row['position_category'] = normalize_position_category($row['position'] ?? '');
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
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'approve_requisition':
            $id = _int(_post('requisition_id'));
            $approvedQtyInput = _int(_post('qty_requested'), 0);
            if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid requisition.'], 422);

            $sql = "SELECT r.*, u.employment_status_id, u.position
                    FROM requisition_items r
                    LEFT JOIN users u ON u.user_id = r.requester_user_id
                    WHERE r.requisition_id = {$id} LIMIT 1";
            $res = call_mysql_query($sql);
            $req = $res ? call_mysql_fetch_array($res) : null;
            if (!$req) json_response(['success' => false, 'message' => 'Requisition not found.'], 404);
            if (!can_access_module_type((string)($req['module_type'] ?? ''))) {
                json_response(['success' => false, 'message' => 'Access denied for this module.'], 403);
            }
            $prevStatus = $req['status'] ?? '';
            $hasClaimAssignment = table_column_exists('requisition_items', 'claim_assignment_id');
            $hasClaimedAt = table_column_exists('requisition_items', 'claimed_at');
            $hasReqIdInAssignments = table_column_exists('facility_records_assignments', 'requisition_id');
            if (requisition_is_claimed($req, $hasClaimAssignment, $hasClaimedAt, $hasReqIdInAssignments)) {
                json_response(['success' => false, 'message' => 'Requisition is already claimed.'], 409);
            }
            if (in_array(strtolower((string)$prevStatus), ['approved', 'claimed', 'disapproved', 'not_claimed'], true)) {
                json_response(['success' => false, 'message' => 'Requisition is already ' . strtolower((string)$prevStatus) . '.'], 409);
            }
            $prevIsAvailable = null;
            $newIsAvailable = null;
            $approvedQty = $approvedQtyInput > 0 ? $approvedQtyInput : (int)($req['qty_requested'] ?? 0);
            if ($approvedQty <= 0) {
                json_response(['success' => false, 'message' => 'Invalid requested quantity.'], 422);
            }

            // Enforce AST availability rules
            if (strtoupper($req['module_type']) === 'AST') {
                $itemCode = _esc($req['item_code']);
                $inv = call_mysql_query("SELECT is_available, allowed_employment_status FROM ast_inventory WHERE property_code = '{$itemCode}' LIMIT 1");
                $invRow = $inv ? call_mysql_fetch_array($inv) : null;
                if (!$invRow) json_response(['success' => false, 'message' => 'AST item not found.'], 404);
                $prevIsAvailable = (int)($invRow['is_available'] ?? 0);
                if ($approvedQty !== 1) {
                    json_response(['success' => false, 'message' => 'AST requests must have quantity 1.'], 422);
                }
                if ($prevIsAvailable !== 1) {
                    json_response(['success' => false, 'message' => 'AST item is no longer available.'], 422);
                }
                $norm = normalize_allowed_employment($invRow['allowed_employment_status'] ?? '');
                if ($norm['mode'] === 'none') {
                    json_response(['success' => false, 'message' => 'Requester is not allowed for this item.'], 422);
                }
                if ($norm['mode'] !== 'all') {
                    $statusId = (int)($req['employment_status_id'] ?? 0);
                    $posCat = normalize_position_category($req['position'] ?? '');
                    if (!requester_is_allowed_for_item($norm, $statusId, $posCat)) {
                        json_response(['success' => false, 'message' => 'Requester employment settings are not allowed for this item.'], 422);
                    }
                }
                $newIsAvailable = 0;
            } else {
                $itemCodeEsc = _esc((string)($req['item_code'] ?? ''));
                $csmQtyColumn = '';
                if (table_column_exists('csm_inventory', 'current_unit_quantity')) {
                    $csmQtyColumn = 'current_unit_quantity';
                } elseif (table_column_exists('csm_inventory', 'quantity')) {
                    $csmQtyColumn = 'quantity';
                }
                $availableQty = (int)($req['qty_requested'] ?? 0);
                if ($csmQtyColumn !== '') {
                    $csmRes = call_mysql_query("SELECT {$csmQtyColumn} AS available_qty, status
                                               FROM csm_inventory
                                               WHERE inventory_system_item_code = '{$itemCodeEsc}'
                                               LIMIT 1");
                    $csmRow = $csmRes ? call_mysql_fetch_array($csmRes) : null;
                    if (!$csmRow) {
                        json_response(['success' => false, 'message' => 'CSM item not found.'], 404);
                    }
                    $csmStatus = strtolower((string)($csmRow['status'] ?? ''));
                    if ($csmStatus !== '' && $csmStatus !== 'available') {
                        json_response(['success' => false, 'message' => 'CSM item is not available.'], 422);
                    }
                    $availableQty = (int)($csmRow['available_qty'] ?? 0);
                }
                if ($availableQty <= 0) {
                    json_response(['success' => false, 'message' => 'No available CSM quantity remaining.'], 422);
                }
                if ($approvedQty > $availableQty) {
                    json_response(['success' => false, 'message' => 'Approved quantity exceeds available CSM quantity (' . $availableQty . ').'], 422);
                }
            }

            call_mysql_query("START TRANSACTION");
            if (strtoupper((string)($req['module_type'] ?? '')) === 'AST') {
                $lockOk = call_mysql_query("UPDATE ast_inventory
                                            SET is_available = 0
                                            WHERE property_code = '{$itemCode}'
                                              AND is_available = 1
                                            LIMIT 1");
                if (!$lockOk || mysqli_affected_rows($db_connect) < 1) {
                    call_mysql_query("ROLLBACK");
                    json_response(['success' => false, 'message' => 'AST item is no longer available.'], 422);
                }
            }
            $setCols = "status='approved', qty_requested = {$approvedQty}, updated_at = NOW()";
            if (table_column_exists('requisition_items', 'approved_by_user_id')) {
                $setCols .= ", approved_by_user_id = " . (int)$s_user_id;
            }
            if (table_column_exists('requisition_items', 'approved_at')) {
                $setCols .= ", approved_at = NOW()";
            }
            $ok = call_mysql_query("UPDATE requisition_items
                                    SET {$setCols}
                                    WHERE requisition_id = {$id}
                                      AND status IN ('pending','reviewed')
                                    LIMIT 1");
            if (!$ok || mysqli_affected_rows($db_connect) < 1) {
                call_mysql_query("ROLLBACK");
                json_response(['success' => false, 'message' => 'Failed to approve requisition. It may have been updated already.'], 409);
            }
            call_mysql_query("COMMIT");
            activity_log_new("REQUISITION APPROVAL", "SUCCESS", array(
                'requisition_id' => $id,
                'module_type' => $req['module_type'] ?? '',
                'item_code' => $req['item_code'] ?? '',
                'item_description' => $req['item_description'] ?? '',
                'qty_requested' => $approvedQty,
                'requester_user_id' => (int)($req['requester_user_id'] ?? 0),
                'old_status' => $prevStatus,
                'new_status' => 'approved',
                'ast_is_available_before' => $prevIsAvailable,
                'ast_is_available_after' => $newIsAvailable
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
            if (table_exists('facility_records_facilities')) {
                $pf = call_mysql_query("SELECT facility_id
                                        FROM facility_records_facilities
                                        WHERE facility_id = {$facilityId}
                                          AND UPPER(TRIM(COALESCE(facility_code, ''))) = 'PERSONAL'
                                        LIMIT 1");
                if ($pf && call_mysql_fetch_array($pf)) {
                    json_response(array(
                        'success' => true,
                        'data' => array(
                            array(
                                'unit_id' => 0,
                                'unit_code' => 'PERSONAL_USE',
                                'unit_name' => 'For Personal use',
                                'unit_type' => 'OTHER',
                                'facility_unit_manager_user_id' => null,
                                'facility_unit_manager_user_ids' => '',
                                'unit_manager_names' => '',
                                'unit_manager_name' => ''
                            )
                        )
                    ));
                }
            }
            $hasUnitManager = table_column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = table_column_exists('facility_records_units', 'accountable_user_id');
            $unitManagerCol = $hasUnitManager ? 'facility_unit_manager_user_id' : ($hasUnitManagerLegacy ? 'accountable_user_id' : '');
            $hasUnitManagersTable = table_exists('facility_records_unit_managers');
            if ($hasUnitManagersTable) {
                $res = call_mysql_query("SELECT
                                            u.unit_id,
                                            u.unit_code,
                                            u.unit_name,
                                            u.unit_type,
                                            MIN(um.user_id) AS facility_unit_manager_user_id,
                                            GROUP_CONCAT(DISTINCT um.user_id ORDER BY ua.l_name SEPARATOR ',') AS facility_unit_manager_user_ids,
                                            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) ORDER BY ua.l_name SEPARATOR '||') AS unit_manager_names,
                                            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) ORDER BY ua.l_name SEPARATOR ', ') AS unit_manager_name
                                         FROM facility_records_units u
                                         LEFT JOIN facility_records_unit_managers um ON um.unit_id = u.unit_id
                                         LEFT JOIN users ua ON ua.user_id = um.user_id
                                         WHERE u.facility_id = {$facilityId} AND u.status = 1
                                         GROUP BY u.unit_id
                                         ORDER BY u.unit_name ASC");
            } else {
                $res = call_mysql_query("SELECT
                                            u.unit_id,
                                            u.unit_code,
                                            u.unit_name,
                                            u.unit_type,
                                            " . ($unitManagerCol !== '' ? "u.{$unitManagerCol}" : "NULL") . " AS facility_unit_manager_user_id,
                                            NULL AS facility_unit_manager_user_ids,
                                            NULL AS unit_manager_names,
                                            CONCAT(COALESCE(ua.f_name,''), ' ', COALESCE(ua.l_name,'')) AS unit_manager_name
                                         FROM facility_records_units u
                                         LEFT JOIN users ua ON ua.user_id = " . ($unitManagerCol !== '' ? "u.{$unitManagerCol}" : "0") . "
                                         WHERE u.facility_id = {$facilityId} AND u.status = 1
                                         ORDER BY u.unit_name ASC");
            }
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
            $remarks = _post('remarks');

            $isPersonalFacility = false;
            if ($facilityId > 0 && table_exists('facility_records_facilities')) {
                $pf = call_mysql_query("SELECT facility_id
                                        FROM facility_records_facilities
                                        WHERE facility_id = {$facilityId}
                                          AND UPPER(TRIM(COALESCE(facility_code, ''))) = 'PERSONAL'
                                        LIMIT 1");
                $isPersonalFacility = ($pf && call_mysql_fetch_array($pf)) ? true : false;
            }
            if ($id <= 0 || $facilityId <= 0 || ($unitId <= 0 && !$isPersonalFacility)) {
                json_response(['success' => false, 'message' => 'Requisition, facility, and unit are required.'], 422);
            }
            if (!table_exists('facility_records_assignments')) {
                json_response(['success' => false, 'message' => 'Facility assignment table is missing.'], 500);
            }

            $reqRes = call_mysql_query("SELECT * FROM requisition_items WHERE requisition_id = {$id} LIMIT 1");
            $req = $reqRes ? call_mysql_fetch_array($reqRes) : null;
            if (!$req) json_response(['success' => false, 'message' => 'Requisition not found.'], 404);
            if (!can_access_module_type((string)($req['module_type'] ?? ''))) {
                json_response(['success' => false, 'message' => 'Access denied for this module.'], 403);
            }
            $issuedToUserId = (int)($req['requester_user_id'] ?? 0);
            $accountableUserId = $issuedToUserId > 0 ? $issuedToUserId : 0;
            if ($issuedToUserId <= 0) {
                json_response(['success' => false, 'message' => 'Requester is missing for this requisition.'], 422);
            }

            $isAlreadyClaimed = false;
            if (table_column_exists('requisition_items', 'claim_assignment_id')) {
                $isAlreadyClaimed = (int)($req['claim_assignment_id'] ?? 0) > 0;
            } elseif (table_column_exists('requisition_items', 'claimed_at')) {
                $isAlreadyClaimed = !empty($req['claimed_at']);
            } elseif (table_column_exists('facility_records_assignments', 'requisition_id')) {
                $chkClaim = call_mysql_query("SELECT assignment_id
                                              FROM facility_records_assignments
                                              WHERE requisition_id = {$id}
                                                AND status <> 'RETURNED'
                                              LIMIT 1");
                $isAlreadyClaimed = ($chkClaim && call_mysql_fetch_array($chkClaim)) ? true : false;
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

            if (!$isPersonalFacility) {
                $unitRes = call_mysql_query("SELECT unit_id FROM facility_records_units WHERE unit_id = {$unitId} AND facility_id = {$facilityId} LIMIT 1");
                if (!$unitRes || !call_mysql_fetch_array($unitRes)) {
                    json_response(['success' => false, 'message' => 'Selected unit does not belong to the facility.'], 422);
                }
            } else {
                $unitId = 0;
            }

            $hasUnitManagersTable = table_exists('facility_records_unit_managers');
            $hasUnitManager = table_column_exists('facility_records_units', 'facility_unit_manager_user_id');
            $hasUnitManagerLegacy = table_column_exists('facility_records_units', 'accountable_user_id');
            $unitManagerCol = $hasUnitManager ? 'facility_unit_manager_user_id' : ($hasUnitManagerLegacy ? 'accountable_user_id' : '');
            $managedByUserId = 0;
            if (!$isPersonalFacility && $hasUnitManagersTable) {
                $mgrRes = call_mysql_query("SELECT MIN(user_id) AS unit_manager_user_id
                                            FROM facility_records_unit_managers
                                            WHERE unit_id = {$unitId}");
                $mgrRow = $mgrRes ? call_mysql_fetch_array($mgrRes) : null;
                $managedByUserId = (int)($mgrRow['unit_manager_user_id'] ?? 0);
            } elseif (!$isPersonalFacility && $unitManagerCol !== '') {
                $mgrRes = call_mysql_query("SELECT {$unitManagerCol} AS unit_manager_user_id
                                            FROM facility_records_units
                                            WHERE unit_id = {$unitId}
                                            LIMIT 1");
                $mgrRow = $mgrRes ? call_mysql_fetch_array($mgrRes) : null;
                $managedByUserId = (int)($mgrRow['unit_manager_user_id'] ?? 0);
            }

            call_mysql_query("START TRANSACTION");

            if ($moduleType === 'AST') {
                $invRes = call_mysql_query("SELECT item_id, property_code, item_description, unit
                                            FROM ast_inventory
                                            WHERE property_code = '{$itemCode}'
                                            LIMIT 1
                                            FOR UPDATE");
                $inv = $invRes ? call_mysql_fetch_array($invRes) : null;
                if (!$inv) {
                    call_mysql_query("ROLLBACK");
                    json_response(['success' => false, 'message' => 'AST inventory item not found.'], 404);
                }
                $sourceItemId = (int)$inv['item_id'];
                $itemDescription = $inv['item_description'] ?: $itemDescription;
                $itemUnit = (string)($inv['unit'] ?? '');
                $qtyRequested = 1;

                $activeRes = call_mysql_query("SELECT assignment_id
                                               FROM facility_records_assignments
                                               WHERE module_type = 'AST'
                                                 AND source_item_id = {$sourceItemId}
                                                 AND status <> 'RETURNED'
                                               LIMIT 1
                                               FOR UPDATE");
                if ($activeRes && call_mysql_fetch_array($activeRes)) {
                    call_mysql_query("ROLLBACK");
                    json_response(['success' => false, 'message' => 'AST item is no longer claimable because it already has an active assignment.'], 409);
                }
            } else {
                $moduleType = 'CSM';
                $invRes = call_mysql_query("SELECT inventory_id, inventory_system_item_code, item_description, item_name, current_unit_quantity
                                            FROM csm_inventory
                                            WHERE inventory_system_item_code = '{$itemCode}' LIMIT 1");
                $inv = $invRes ? call_mysql_fetch_array($invRes) : null;
                if (!$inv) {
                    call_mysql_query("ROLLBACK");
                    json_response(['success' => false, 'message' => 'CSM inventory item not found.'], 404);
                }
                $available = (int)($inv['current_unit_quantity'] ?? 0);
                if ($available < $qtyRequested) {
                    call_mysql_query("ROLLBACK");
                    json_response(['success' => false, 'message' => 'CSM quantity is not enough for claim.'], 422);
                }
                $sourceItemId = (int)$inv['inventory_id'];
                $itemDescription = $inv['item_description'] ?: ($inv['item_name'] ?? $itemDescription);
                $itemUnit = '';
                $decOk = call_mysql_query("UPDATE csm_inventory
                                           SET current_unit_quantity = current_unit_quantity - {$qtyRequested}
                                           WHERE inventory_id = {$sourceItemId}
                                             AND current_unit_quantity >= {$qtyRequested}
                                           LIMIT 1");
                if (!$decOk || mysqli_affected_rows($db_connect) < 1) {
                    call_mysql_query("ROLLBACK");
                    json_response(['success' => false, 'message' => 'CSM quantity is not enough for claim.'], 422);
                }
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
            if (!$okAssign) {
                call_mysql_query("ROLLBACK");
                json_response(['success' => false, 'message' => 'Failed to create facility assignment.'], 500);
            }
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
            $reqSet .= ", status = 'claimed'";
            $okReq = call_mysql_query("UPDATE requisition_items
                                       SET {$reqSet}
                                       WHERE requisition_id = {$id}
                                         AND status IN ('approved','reviewed')
                                       LIMIT 1");
            if (!$okReq || mysqli_affected_rows($db_connect) < 1) {
                call_mysql_query("ROLLBACK");
                json_response(['success' => false, 'message' => 'Claim saved but failed to update requisition state.'], 500);
            }
            call_mysql_query("COMMIT");

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

        case 'decline_requisition_claim':
            $id = _int(_post('requisition_id'));
            if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid requisition.'], 422);
            $reqRes = call_mysql_query("SELECT * FROM requisition_items WHERE requisition_id = {$id} LIMIT 1");
            $req = $reqRes ? call_mysql_fetch_array($reqRes) : null;
            if (!$req) json_response(['success' => false, 'message' => 'Requisition not found.'], 404);
            if (!can_access_module_type((string)($req['module_type'] ?? ''))) {
                json_response(['success' => false, 'message' => 'Access denied for this module.'], 403);
            }
            $status = strtolower((string)($req['status'] ?? ''));
            if (!in_array($status, ['approved', 'reviewed'], true)) {
                json_response(['success' => false, 'message' => 'Only for-claiming requisitions can be moved back to storage.'], 409);
            }
            $hasClaimAssignment = table_column_exists('requisition_items', 'claim_assignment_id');
            $hasClaimedAt = table_column_exists('requisition_items', 'claimed_at');
            $hasReqIdInAssignments = table_column_exists('facility_records_assignments', 'requisition_id');
            if (requisition_is_claimed($req, $hasClaimAssignment, $hasClaimedAt, $hasReqIdInAssignments)) {
                json_response(['success' => false, 'message' => 'Requisition is already claimed.'], 409);
            }

            call_mysql_query("START TRANSACTION");
            if (strtoupper((string)($req['module_type'] ?? '')) === 'AST') {
                $itemCode = _esc((string)($req['item_code'] ?? ''));
                $itemRes = call_mysql_query("SELECT item_id
                                             FROM ast_inventory
                                             WHERE property_code = '{$itemCode}'
                                             LIMIT 1
                                             FOR UPDATE");
                $item = $itemRes ? call_mysql_fetch_array($itemRes) : null;
                if ($item) {
                    call_mysql_query("UPDATE ast_inventory SET is_available = 1 WHERE item_id = " . (int)$item['item_id'] . " LIMIT 1");
                }
            }
            $ok = call_mysql_query("UPDATE requisition_items
                                    SET status = 'not_claimed',
                                        updated_at = NOW()
                                    WHERE requisition_id = {$id}
                                      AND status IN ('approved','reviewed')
                                    LIMIT 1");
            if (!$ok || mysqli_affected_rows($db_connect) < 1) {
                call_mysql_query("ROLLBACK");
                json_response(['success' => false, 'message' => 'Failed to move requisition back to storage.'], 500);
            }
            call_mysql_query("COMMIT");
            activity_log_new("REQUISITION BACK TO STORAGE", "SUCCESS", array(
                'requisition_id' => $id,
                'module_type' => $req['module_type'] ?? '',
                'item_code' => $req['item_code'] ?? ''
            ));
            json_response(['success' => true, 'message' => 'Requisition moved back to storage.']);
            break;

        case 'disapprove_requisition':
            $id = _int(_post('requisition_id'));
            $reason = _post('reason');
            if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid requisition.'], 422);
            if ($reason === '') json_response(['success' => false, 'message' => 'Reason is required.'], 422);
            $reqRes = call_mysql_query("SELECT * FROM requisition_items WHERE requisition_id = {$id} LIMIT 1");
            $req = $reqRes ? call_mysql_fetch_array($reqRes) : null;
            if (!$req) json_response(['success' => false, 'message' => 'Requisition not found.'], 404);
            if (!can_access_module_type((string)($req['module_type'] ?? ''))) {
                json_response(['success' => false, 'message' => 'Access denied for this module.'], 403);
            }
            $prevStatus = $req['status'] ?? '';
            $rawPrev = strtolower((string)$prevStatus);
            if (!in_array($rawPrev, ['pending', 'reviewed'], true)) {
                json_response(['success' => false, 'message' => 'Only pending/reviewed requisitions can be disapproved.'], 409);
            }
            call_mysql_query("START TRANSACTION");
            $ok = call_mysql_query("UPDATE requisition_items
                                    SET status='disapproved',
                                        reason='" . _esc($reason) . "',
                                        updated_at = NOW()
                                    WHERE requisition_id = {$id}
                                      AND status IN ('pending','reviewed')
                                    LIMIT 1");
            if (!$ok || mysqli_affected_rows($db_connect) < 1) {
                call_mysql_query("ROLLBACK");
                json_response(['success' => false, 'message' => 'Failed to disapprove requisition.'], 500);
            }
            call_mysql_query("COMMIT");
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
    call_mysql_query("ROLLBACK");
    json_response(['success' => false, 'message' => 'Server error occurred.'], 500);
}
