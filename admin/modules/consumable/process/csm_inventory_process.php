<?php
// process/csm_inventory_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// NOTE: default is plain text, but list/get returns JSON when needed
header('Content-Type: text/plain; charset=utf-8');

// -------------------- ACCESS CONTROL --------------------
$isStaffCsm = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("CSM", "PO")));
if (!(role_has("ADMIN") || $isStaffCsm)) {
    http_response_code(403);
    echo "Access denied.";
    exit();
}

// -------------------- HELPERS --------------------
function _post($k, $default = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default; }
function _int($v, $default = 0) { return ($v === '' || $v === null) ? $default : (int)$v; }
function _float($v, $default = 0.0) { return ($v === '' || $v === null) ? $default : (float)$v; }

// DO NOT REMOVE THIS SPECIFIC CODE - USED FOR SQL INJECTION PREVENTION
function _esc($v) {
    global $conn;
    $v = (string)$v;
    if (isset($conn) && $conn instanceof mysqli) return mysqli_real_escape_string($conn, $v);
    return addslashes($v);
}

// DB uses DATE for last_updated, and DATE for acquisition_date
function _today() { return date('Y-m-d'); }

/**
 * Accepts blank, digits, OR "CSM0001 / CSM-0001 / csm 0001".
 * Returns:
 * - '' if blank
 * - raw digits string (may include leading zeros) if valid
 * - '__INVALID__' if invalid
 */
function _only_digits_or_blank($s) {
    $s = trim((string)$s);
    if ($s === '') return '';

    $compact = preg_replace('/\s+/', '', $s);

    if (preg_match('/^\d+$/', $compact)) return $compact;

    if (preg_match('/^CSM[-_]*\d+$/i', $compact)) {
        if (preg_match('/\d+$/', $compact, $m)) return (string)$m[0];
    }

    return '__INVALID__';
}

/**
 * Normalize category code to avoid: "CSM-CSM0002-0001"
 * If DB contains "CSM0002" or "CSM-0002", normalize to "0002"
 * Otherwise keep original
 */
function _normalize_category_code_for_itemcode($catCode) {
    $catCode = trim((string)$catCode);
    if ($catCode === '') return '';
    if (preg_match('/^CSM-?(\d+)$/i', $catCode, $m)) {
        return (string)$m[1];
    }
    return $catCode;
}

function _category_exists($catCode) {
    $catCode = trim((string)$catCode);
    if ($catCode === '') return false;
    $catEsc = _esc($catCode);
    $sql = "SELECT item_category_code FROM csm_inventory_category WHERE item_category_code='{$catEsc}' LIMIT 1";
    $res = call_mysql_query($sql);
    return ($res && call_mysql_fetch_array($res)) ? true : false;
}

function _format_item_code($catCode, $numStr) {
    $catCode = _normalize_category_code_for_itemcode($catCode);
    $numStr = trim((string)$numStr);
    $n = (int)$numStr;
    if ($catCode === '' || $n <= 0) return '';
    return "CSM-{$catCode}-" . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

function _next_number_for_category($catCode) {
    $catCodeNorm = _normalize_category_code_for_itemcode($catCode);
    if ($catCodeNorm === '') return 1;

    $prefix = "CSM-{$catCodeNorm}-";
    $prefixEsc = _esc($prefix);

    $sql = "SELECT inventory_system_item_code
            FROM csm_inventory
            WHERE inventory_system_item_code LIKE '{$prefixEsc}%'
            ORDER BY inventory_system_item_code DESC
            LIMIT 1";
    $res = call_mysql_query($sql);

    $next = 1;
    if ($res && ($row = call_mysql_fetch_array($res)) && !empty($row['inventory_system_item_code'])) {
        $last = (string)$row['inventory_system_item_code'];
        $suffix4 = substr($last, -4);
        if (ctype_digit($suffix4)) $next = ((int)$suffix4) + 1;
    }
    if ($next <= 0) $next = 1;
    return $next;
}

function _generate_item_code_auto($catCode) {
    $n = _next_number_for_category($catCode);
    return _format_item_code($catCode, (string)$n);
}

/**
 * Canonicalize scanned / pasted item code:
 * - remove spaces
 * - accept exact "CSM-XXXX-0001"
 */
function _canon_item_code($code) {
    $code = trim((string)$code);
    if ($code === '') return '';
    $c = preg_replace('/\s+/', '', $code);
    if (preg_match('/^CSM-([A-Za-z0-9\-_]+)-(\d{4})$/i', $c, $m)) {
        return "CSM-" . $m[1] . "-" . $m[2];
    }
    return $c;
}

// -------------------- EMPLOYMENT STATUS / RULE HELPERS --------------------
function get_employment_status_map_csm() {
    $map = [];
    $res = call_mysql_query("SELECT employment_status_id, status_name, status_code FROM employment_status ORDER BY employment_status_id ASC");
    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            $map[(int)$row['employment_status_id']] = [
                'status_name' => $row['status_name'] ?: ($row['status_code'] ?? ''),
                'status_code' => $row['status_code'] ?? ''
            ];
        }
    }
    return $map;
}

function normalize_allowed_employment_csm($raw) {
    if ($raw === null || $raw === '') {
        return [
            'mode' => 'all',
            'teaching' => [],
            'non_teaching' => [],
            'selected_ids' => []
        ];
    }

    $decoded = is_array($raw) ? $raw : json_decode((string)$raw, true);

    if (!is_array($decoded)) {
        $s = trim((string)$raw);

        if ($s === '' || strcasecmp($s, 'ALL') === 0) {
            return ['mode' => 'all', 'teaching' => [], 'non_teaching' => [], 'selected_ids' => []];
        }

        if (strcasecmp($s, 'NONE') === 0) {
            return ['mode' => 'none', 'teaching' => [], 'non_teaching' => [], 'selected_ids' => []];
        }

        $parts = preg_split('/\s*,\s*/', $s);
        $ids = array_values(array_filter(array_map('intval', $parts)));

        if (!empty($ids)) {
            return [
                'mode' => 'legacy',
                'teaching' => $ids,
                'non_teaching' => $ids,
                'selected_ids' => $ids
            ];
        }

        return ['mode' => 'all', 'teaching' => [], 'non_teaching' => [], 'selected_ids' => []];
    }

    if (!empty($decoded['none'])) {
        return ['mode' => 'none', 'teaching' => [], 'non_teaching' => [], 'selected_ids' => []];
    }

    if (array_key_exists('teaching', $decoded) || array_key_exists('non_teaching', $decoded)) {
        $teaching = array_values(array_filter(array_map('intval', (array)($decoded['teaching'] ?? []))));
        $non = array_values(array_filter(array_map('intval', (array)($decoded['non_teaching'] ?? []))));
        $selected = array_values(array_unique(array_merge($teaching, $non)));

        if (empty($teaching) && empty($non)) {
            return ['mode' => 'all', 'teaching' => [], 'non_teaching' => [], 'selected_ids' => []];
        }

        return [
            'mode' => 'structured',
            'teaching' => $teaching,
            'non_teaching' => $non,
            'selected_ids' => $selected
        ];
    }

    $list = array_values($decoded);
    $listStr = array_map('strval', $list);

    if (in_array('NONE', $listStr, true)) {
        return ['mode' => 'none', 'teaching' => [], 'non_teaching' => [], 'selected_ids' => []];
    }

    $ids = array_values(array_filter(array_map('intval', $list)));
    if (empty($ids)) {
        return ['mode' => 'all', 'teaching' => [], 'non_teaching' => [], 'selected_ids' => []];
    }

    return [
        'mode' => 'legacy',
        'teaching' => $ids,
        'non_teaching' => $ids,
        'selected_ids' => $ids
    ];
}

function normalize_allowed_payload_csm($raw) {
    if ($raw === null || $raw === '') {
        return [
            'mode' => 'all',
            'json' => null,
            'teaching' => [],
            'non_teaching' => []
        ];
    }

    $s = trim((string)$raw);

    if ($s === '' || strcasecmp($s, 'ALL') === 0) {
        return ['mode' => 'all', 'json' => null, 'teaching' => [], 'non_teaching' => []];
    }

    if (strcasecmp($s, 'NONE') === 0) {
        return [
            'mode' => 'none',
            'json' => json_encode(['none' => true]),
            'teaching' => [],
            'non_teaching' => []
        ];
    }

    $decoded = json_decode($s, true);
    if (is_array($decoded) && (array_key_exists('teaching', $decoded) || array_key_exists('non_teaching', $decoded) || array_key_exists('none', $decoded))) {
        if (!empty($decoded['none'])) {
            return [
                'mode' => 'none',
                'json' => json_encode(['none' => true]),
                'teaching' => [],
                'non_teaching' => []
            ];
        }

        $teaching = array_values(array_filter(array_map('intval', (array)($decoded['teaching'] ?? []))));
        $non = array_values(array_filter(array_map('intval', (array)($decoded['non_teaching'] ?? []))));

        if (empty($teaching) && empty($non)) {
            return ['mode' => 'all', 'json' => null, 'teaching' => [], 'non_teaching' => []];
        }

        return [
            'mode' => 'structured',
            'json' => json_encode(['teaching' => $teaching, 'non_teaching' => $non]),
            'teaching' => $teaching,
            'non_teaching' => $non
        ];
    }

    $parts = preg_split('/\s*,\s*/', $s);
    $ids = array_values(array_filter(array_map('intval', $parts)));

    if (empty($ids)) {
        return ['mode' => 'all', 'json' => null, 'teaching' => [], 'non_teaching' => []];
    }

    return [
        'mode' => 'legacy',
        'json' => json_encode(['teaching' => $ids, 'non_teaching' => $ids]),
        'teaching' => $ids,
        'non_teaching' => $ids
    ];
}

function build_allowed_status_label_csm($norm, $statusMap) {
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
            if (isset($statusMap[$sid])) $names[] = $statusMap[$sid]['status_name'];
        }
        $labels[] = 'Teaching: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Teaching: None';
    }

    if (!empty($non)) {
        $names = [];
        foreach ($non as $sid) {
            $sid = (int)$sid;
            if (isset($statusMap[$sid])) $names[] = $statusMap[$sid]['status_name'];
        }
        $labels[] = 'Non-Teaching: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Non-Teaching: None';
    }

    return implode(' | ', $labels);
}

/*
0 = Unavailable
1 = Available
2 = Stock Critical
3 = Out of Stock
*/
function _compute_status_from_state($currentQty, $critLevel, $allowedRaw) {
    $currentQty = (int)$currentQty;
    $critLevel  = (int)$critLevel;

    $norm = normalize_allowed_employment_csm($allowedRaw);
    $mode = $norm['mode'] ?? 'all';

    if ($mode === 'none') {
        return 0;
    }

    if ($currentQty <= 0) {
        return 3;
    }

    if ($critLevel > 0 && $currentQty <= $critLevel) {
        return 2;
    }

    return 1;
}

function enrich_csm_row($row, $statusMap) {
    if (!$row) return $row;

    $norm = normalize_allowed_employment_csm($row['allowed_employment_status'] ?? '');
    $row['allowed_status_names'] = build_allowed_status_label_csm($norm, $statusMap);
    $row['allowed_employment_status'] = [
        'mode' => $norm['mode'],
        'teaching' => $norm['teaching'],
        'non_teaching' => $norm['non_teaching'],
        'selected_ids' => $norm['selected_ids']
    ];
    $row['status'] = (int)($row['status'] ?? 0);

    return $row;
}

/**
 * Resolve display_image and include new unit/cost_value fields
 */
function _list_inventory_sql($where = "", $order = "ORDER BY i.created_at DESC", $limit = "") {
    $whereSql = $where ? "WHERE {$where}" : "";
    $limitSql = $limit ? "LIMIT {$limit}" : "";

    return "
        SELECT
            i.*,
            c.category_id AS category_id_ref,
            c.item_category_name,

            (
                CASE
                    WHEN i.item_category_img IS NULL OR TRIM(i.item_category_img) = '' THEN NULL
                    WHEN TRIM(i.item_category_img) REGEXP '^[0-9]+$' THEN (
                        SELECT ci2.file_url
                        FROM csm_inventory_category_images ci2
                        WHERE ci2.image_id = CAST(TRIM(i.item_category_img) AS UNSIGNED)
                        LIMIT 1
                    )
                    WHEN TRIM(i.item_category_img) LIKE 'upload/%' THEN TRIM(i.item_category_img)
                    WHEN TRIM(i.item_category_img) LIKE '%/%' THEN TRIM(i.item_category_img)
                    ELSE CONCAT('upload/category/', TRIM(i.item_category_img))
                END
            ) AS assigned_image_url,

            COALESCE(
                (
                    CASE
                        WHEN i.item_category_img IS NULL OR TRIM(i.item_category_img) = '' THEN NULL
                        WHEN TRIM(i.item_category_img) REGEXP '^[0-9]+$' THEN (
                            SELECT ci2.file_url
                            FROM csm_inventory_category_images ci2
                            WHERE ci2.image_id = CAST(TRIM(i.item_category_img) AS UNSIGNED)
                            LIMIT 1
                        )
                        WHEN TRIM(i.item_category_img) LIKE 'upload/%' THEN TRIM(i.item_category_img)
                        WHEN TRIM(i.item_category_img) LIKE '%/%' THEN TRIM(i.item_category_img)
                        ELSE CONCAT('upload/category/', TRIM(i.item_category_img))
                    END
                ),
                (
                    SELECT ci.file_url
                    FROM csm_inventory_category_images ci
                    WHERE ci.category_id = c.category_id
                    ORDER BY (CASE WHEN IFNULL(ci.is_primary,0)=1 THEN 0 ELSE 1 END), ci.image_id ASC
                    LIMIT 1
                )
            ) AS display_image

        FROM csm_inventory i
        LEFT JOIN csm_inventory_category c
            ON c.item_category_code = i.item_category_code
        {$whereSql}
        {$order}
        {$limitSql}
    ";
}

$action = _post('action', '');
if ($action === '') {
    http_response_code(400);
    echo "Missing action.";
    exit();
}

// -------------------- ROUTER --------------------
try {
    switch ($action) {

        // ============ CREATE ============
        case 'add_inventory': {
            $raw_code_numeric   = _post('inventory_system_item_code');
            $item_description   = _post('item_description');
            $item_category_code = _post('item_category_code');

            $cost_value            = _float(_post('cost_value'));
            $unit                  = _post('unit');
            $quantity         = _int(_post('quantity'));
            $current_quantity = _int(_post('current_quantity'));
            $qty_crit_level       = _int(_post('qty_crit_level'));

            $source_of_funds = _post('source_of_funds');
            $allowed_norm = normalize_allowed_payload_csm(_post('allowed_status', 'ALL'));

            if ($item_description === '' || $item_category_code === '' || $unit === '') {
                http_response_code(422);
                echo "Required fields missing (Itemized Description, Category, Unit).";
                exit();
            }
            if (!_category_exists($item_category_code)) {
                http_response_code(422);
                echo "Unknown Category Code: " . $item_category_code;
                exit();
            }
            if ($quantity < 0 || $current_quantity < 0 || $qty_crit_level < 0 || $cost_value < 0) {
                http_response_code(422);
                echo "Cost, quantity, and critical level cannot be negative.";
                exit();
            }
            if ($current_quantity > $quantity) {
                http_response_code(422);
                echo "Available quantity cannot exceed Total Quantity.";
                exit();
            }

            $digitsRaw = _only_digits_or_blank($raw_code_numeric);
            if ($digitsRaw === '__INVALID__') {
                http_response_code(422);
                echo "Item Code must be numbers only (or blank). You may also input CSM0001 / CSM-0001.";
                exit();
            }

            $digits = ($digitsRaw === '') ? '' : (string)intval($digitsRaw, 10);
            if ($digits !== '' && $digits === '0') {
                http_response_code(422);
                echo "Invalid Item Code number.";
                exit();
            }

            if ($digits === '') {
                $inventory_system_item_code = _generate_item_code_auto($item_category_code);
            } else {
                $inventory_system_item_code = _format_item_code($item_category_code, $digits);
                if ($inventory_system_item_code === '') {
                    http_response_code(422);
                    echo "Invalid Item Code number.";
                    exit();
                }
            }

            $codeEsc = _esc($inventory_system_item_code);
            $checkSql = "SELECT inventory_id FROM csm_inventory
                         WHERE inventory_system_item_code = '{$codeEsc}' LIMIT 1";
            $checkRes = call_mysql_query($checkSql);
            if ($checkRes && call_mysql_fetch_array($checkRes)) {
                http_response_code(409);
                echo "Item Code already exists.";
                exit();
            }

            $today = _today();
            $allowed_json = $allowed_norm['json'];
            $status = _compute_status_from_state($current_quantity, $qty_crit_level, $allowed_json);

            $sql = "
                INSERT INTO csm_inventory
                (
                    inventory_system_item_code,
                    item_description,
                    acquisition_date,
                    cost_value,
                    unit,
                    source_of_funds,
                    item_category_code,
                    status,
                    quantity,
                    current_quantity,
                    qty_crit_level,
                    allowed_employment_status,
                    last_updated
                )
                VALUES
                (
                    '" . _esc($inventory_system_item_code) . "',
                    '" . _esc($item_description) . "',
                    '" . _esc($today) . "',
                    " . (float)$cost_value . ",
                    '" . _esc($unit) . "',
                    '" . _esc($source_of_funds) . "',
                    '" . _esc($item_category_code) . "',
                    " . (int)$status . ",
                    " . (int)$quantity . ",
                    " . (int)$current_quantity . ",
                    " . (int)$qty_crit_level . ",
                    " . ($allowed_json ? "'" . _esc($allowed_json) . "'" : "NULL") . ",
                    '" . _esc($today) . "'
                )
            ";

            $res = call_mysql_query($sql);
            if ($res) { echo "success"; exit(); }

            http_response_code(500);
            echo "Database insert failed.";
            exit();
        }

        // ============ READ (LIST) ============
        case 'list_inventory': {
            header('Content-Type: application/json; charset=utf-8');

            $sql = _list_inventory_sql("", "ORDER BY i.created_at DESC", "");
            $result = call_mysql_query($sql);

            $items = [];
            $statusMap = get_employment_status_map_csm();
            if ($result) {
                while ($row = call_mysql_fetch_array($result)) {
                    $items[] = enrich_csm_row($row, $statusMap);
                }
            }
            echo json_encode(['success' => true, 'data' => $items]);
            exit();
        }

        // ============ READ (RECENT ADDED) ============
        case 'list_recent_added': {
            header('Content-Type: application/json; charset=utf-8');

            $sql = _list_inventory_sql("", "ORDER BY i.created_at DESC", "200");
            $result = call_mysql_query($sql);

            $items = [];
            $statusMap = get_employment_status_map_csm();
            if ($result) {
                while ($row = call_mysql_fetch_array($result)) {
                    $items[] = enrich_csm_row($row, $statusMap);
                }
            }
            echo json_encode(['success' => true, 'data' => $items]);
            exit();
        }

        // ============ READ (ONE) ============
        case 'get_inventory': {
            header('Content-Type: application/json; charset=utf-8');
            $inventory_id = _int(_post('inventory_id'));
            if ($inventory_id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid inventory_id.']);
                exit();
            }

            $sql = _list_inventory_sql("i.inventory_id = {$inventory_id}", "ORDER BY i.inventory_id DESC", "1");
            $result = call_mysql_query($sql);
            $row = $result ? call_mysql_fetch_array($result) : null;

            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Record not found.']);
                exit();
            }

            $statusMap = get_employment_status_map_csm();
            $row = enrich_csm_row($row, $statusMap);

            echo json_encode(['success' => true, 'data' => $row]);
            exit();
        }

        // ============ FIND BY CODE (SCAN/SEARCH) ============
        case 'find_item_by_code': {
            header('Content-Type: application/json; charset=utf-8');

            $codeIn = _canon_item_code(_post('inventory_system_item_code'));
            if ($codeIn === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Missing inventory_system_item_code.']);
                exit();
            }

            $codeEsc = _esc($codeIn);
            $sql = _list_inventory_sql("i.inventory_system_item_code = '{$codeEsc}'", "ORDER BY i.inventory_id DESC", "1");
            $result = call_mysql_query($sql);
            $row = $result ? call_mysql_fetch_array($result) : null;

            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found.']);
                exit();
            }

            $statusMap = get_employment_status_map_csm();
            $row = enrich_csm_row($row, $statusMap);

            echo json_encode(['success' => true, 'data' => $row]);
            exit();
        }

        // ============ EMPLOYMENT STATUS OPTIONS ============
        case 'list_employment_status': {
            header('Content-Type: application/json; charset=utf-8');

            $rows = [];
            $res = call_mysql_query("SELECT employment_status_id, status_name, status_code FROM employment_status ORDER BY employment_status_id ASC");
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = [
                        'employment_status_id' => (int)$row['employment_status_id'],
                        'status_name' => $row['status_name'],
                        'status_code' => $row['status_code']
                    ];
                }
            }

            echo json_encode(['success' => true, 'data' => $rows]);
            exit();
        }

        // ============ GET RULES / AVAILABILITY SETTINGS ============
        case 'get_availability_settings': {
            header('Content-Type: application/json; charset=utf-8');

            $inventory_id = _int(_post('inventory_id'));
            if ($inventory_id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid inventory_id.']);
                exit();
            }

            $sql = "SELECT inventory_id, inventory_system_item_code, item_description, unit, quantity, current_quantity, qty_crit_level, status, allowed_employment_status
                    FROM csm_inventory
                    WHERE inventory_id = {$inventory_id}
                    LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;

            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found.']);
                exit();
            }

            $norm = normalize_allowed_employment_csm($row['allowed_employment_status'] ?? '');
            $row['allowed_employment_status'] = [
                'mode' => $norm['mode'],
                'teaching' => $norm['teaching'],
                'non_teaching' => $norm['non_teaching'],
                'selected_ids' => $norm['selected_ids']
            ];
            $row['status'] = (int)($row['status'] ?? 0);

            echo json_encode(['success' => true, 'data' => $row]);
            exit();
        }

        // ============ UPDATE RULES / AVAILABILITY ============
        case 'update_availability_settings': {
            header('Content-Type: application/json; charset=utf-8');

            $inventory_id = _int(_post('inventory_id'));
            $current_quantity = _int(_post('current_quantity'));
            $allowed_norm = normalize_allowed_payload_csm(_post('allowed_status', 'ALL'));

            if ($inventory_id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid inventory_id.']);
                exit();
            }
            if ($current_quantity < 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Available quantity cannot be negative.']);
                exit();
            }

            $chk = call_mysql_query("SELECT inventory_id, qty_crit_level, quantity FROM csm_inventory WHERE inventory_id={$inventory_id} LIMIT 1");
            $r = $chk ? call_mysql_fetch_array($chk) : null;
            if (!$r) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Record not found.']);
                exit();
            }

            $unitQty = (int)$r['quantity'];
            $critLevel = (int)$r['qty_crit_level'];

            if ($current_quantity > $unitQty) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => "Available quantity cannot exceed total quantity ({$unitQty})."]);
                exit();
            }

            $allowed_json = $allowed_norm['json'];
            $status = _compute_status_from_state($current_quantity, $critLevel, $allowed_json);
            $today = _today();

            $sql = "
                UPDATE csm_inventory
                SET
                    current_quantity = " . (int)$current_quantity . ",
                    allowed_employment_status = " . ($allowed_json ? "'" . _esc($allowed_json) . "'" : "NULL") . ",
                    status = " . (int)$status . ",
                    last_updated = '" . _esc($today) . "'
                WHERE inventory_id = {$inventory_id}
                LIMIT 1
            ";

            $res = call_mysql_query($sql);
            if ($res) {
                echo json_encode(['success' => true, 'message' => 'Availability rules updated.']);
                exit();
            }

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database update failed.']);
            exit();
        }

        // ============ UPDATE (MAIN RECORD, does NOT touch current_quantity except clamp) ============
        case 'update_inventory': {
            $inventory_id = _int(_post('inventory_id'));
            if ($inventory_id <= 0) {
                http_response_code(422);
                echo "Invalid inventory_id.";
                exit();
            }

            $raw_code_numeric   = _post('inventory_system_item_code');
            $item_description   = _post('item_description');
            $item_category_code = _post('item_category_code');

            $cost_value      = _float(_post('cost_value'));
            $unit            = _post('unit');
            $quantity   = _int(_post('quantity'));
            $qty_crit_level = _int(_post('qty_crit_level'));

            $source_of_funds = _post('source_of_funds');

            if ($item_description === '' || $item_category_code === '' || $unit === '') {
                http_response_code(422);
                echo "Required fields missing (Itemized Description, Category, Unit).";
                exit();
            }
            if (!_category_exists($item_category_code)) {
                http_response_code(422);
                echo "Unknown Category Code: " . $item_category_code;
                exit();
            }
            if ($cost_value < 0 || $quantity < 0 || $qty_crit_level < 0) {
                http_response_code(422);
                echo "Cost, quantity, and critical level cannot be negative.";
                exit();
            }

            $digitsRaw = _only_digits_or_blank($raw_code_numeric);
            if ($digitsRaw === '__INVALID__') {
                http_response_code(422);
                echo "Item Code must be numbers only (or blank). You may also input CSM0001 / CSM-0001.";
                exit();
            }

            $digits = ($digitsRaw === '') ? '' : (string)intval($digitsRaw, 10);
            if ($digits !== '' && $digits === '0') {
                http_response_code(422);
                echo "Invalid Item Code number.";
                exit();
            }

            if ($digits === '') {
                $inventory_system_item_code = _generate_item_code_auto($item_category_code);
            } else {
                $inventory_system_item_code = _format_item_code($item_category_code, $digits);
                if ($inventory_system_item_code === '') {
                    http_response_code(422);
                    echo "Invalid Item Code number.";
                    exit();
                }
            }

            $codeEsc = _esc($inventory_system_item_code);
            $dupSql = "SELECT inventory_id FROM csm_inventory
                       WHERE inventory_system_item_code = '{$codeEsc}'
                       AND inventory_id <> {$inventory_id}
                       LIMIT 1";
            $dupRes = call_mysql_query($dupSql);
            if ($dupRes && call_mysql_fetch_array($dupRes)) {
                http_response_code(409);
                echo "Item Code already exists on another record.";
                exit();
            }

            $oldRes = call_mysql_query("SELECT current_quantity, allowed_employment_status FROM csm_inventory WHERE inventory_id={$inventory_id} LIMIT 1");
            $oldRow = $oldRes ? call_mysql_fetch_array($oldRes) : null;
            $currentQty = $oldRow ? (int)$oldRow['current_quantity'] : 0;
            if ($currentQty > $quantity) $currentQty = $quantity;

            $status = _compute_status_from_state($currentQty, $qty_crit_level, $oldRow['allowed_employment_status'] ?? null);
            $today = _today();

            $sql = "
                UPDATE csm_inventory
                SET
                    inventory_system_item_code = '" . _esc($inventory_system_item_code) . "',
                    item_description = '" . _esc($item_description) . "',
                    item_category_code = '" . _esc($item_category_code) . "',
                    cost_value = " . (float)$cost_value . ",
                    unit = '" . _esc($unit) . "',
                    quantity = " . (int)$quantity . ",
                    current_quantity = " . (int)$currentQty . ",
                    qty_crit_level = " . (int)$qty_crit_level . ",
                    source_of_funds = '" . _esc($source_of_funds) . "',
                    status = " . (int)$status . ",
                    last_updated = '" . _esc($today) . "'
                WHERE inventory_id = {$inventory_id}
                LIMIT 1
            ";

            $res = call_mysql_query($sql);
            if ($res) { echo "success"; exit(); }

            http_response_code(500);
            echo "Database update failed.";
            exit();
        }

        // ============ ADD QUANTITY (existing item) ============
        case 'add_quantity': {
            header('Content-Type: application/json; charset=utf-8');

            $inventory_id = _int(_post('inventory_id'));
            $add_qty      = _int(_post('add_quantity'));

            $source_of_funds = _post('source_of_funds');
            $cost_value_raw  = _post('cost_value');

            if ($inventory_id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid inventory_id.']);
                exit();
            }
            if ($add_qty <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Add quantity must be at least 1.']);
                exit();
            }

            $q = call_mysql_query("SELECT quantity, current_quantity, qty_crit_level, source_of_funds, cost_value, allowed_employment_status FROM csm_inventory WHERE inventory_id={$inventory_id} LIMIT 1");
            $row = $q ? call_mysql_fetch_array($q) : null;
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found.']);
                exit();
            }

            $new_unit_qty = (int)$row['quantity'] + $add_qty;
            $new_cur_qty  = (int)$row['current_quantity'] + $add_qty;
            $critLevel    = (int)$row['qty_crit_level'];

            $final_source = ($source_of_funds === '') ? (string)$row['source_of_funds'] : $source_of_funds;
            $final_cost = (trim($cost_value_raw) !== '') ? (string)_float($cost_value_raw) : (string)$row['cost_value'];

            $status = _compute_status_from_state($new_cur_qty, $critLevel, $row['allowed_employment_status'] ?? null);
            $today = _today();

            $sql = "
                UPDATE csm_inventory
                SET
                    quantity = {$new_unit_qty},
                    current_quantity = {$new_cur_qty},
                    source_of_funds = '" . _esc($final_source) . "',
                    cost_value = " . (float)$final_cost . ",
                    status = " . (int)$status . ",
                    last_updated = '" . _esc($today) . "'
                WHERE inventory_id = {$inventory_id}
                LIMIT 1
            ";

            $res = call_mysql_query($sql);
            if (!$res) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database update failed.']);
                exit();
            }

            echo json_encode(['success' => true]);
            exit();
        }

        // ============ UPDATE AVAILABLE ONLY ============
        case 'update_available_qty': {
            $inventory_id = _int(_post('inventory_id'));
            $current_quantity = _int(_post('current_quantity'));

            if ($inventory_id <= 0) {
                http_response_code(422);
                echo "Invalid inventory_id.";
                exit();
            }
            if ($current_quantity < 0) {
                http_response_code(422);
                echo "Available quantity cannot be negative.";
                exit();
            }

            $chk = call_mysql_query("SELECT quantity, qty_crit_level, allowed_employment_status FROM csm_inventory WHERE inventory_id={$inventory_id} LIMIT 1");
            $r = $chk ? call_mysql_fetch_array($chk) : null;
            if (!$r) {
                http_response_code(404);
                echo "Record not found.";
                exit();
            }

            $unitQty = (int)$r['quantity'];
            $critLevel = (int)$r['qty_crit_level'];

            if ($current_quantity > $unitQty) {
                http_response_code(422);
                echo "Available quantity cannot exceed Total Quantity ({$unitQty}).";
                exit();
            }

            $today = _today();
            $status = _compute_status_from_state($current_quantity, $critLevel, $r['allowed_employment_status'] ?? null);

            $sql = "
                UPDATE csm_inventory
                SET
                    current_quantity = " . (int)$current_quantity . ",
                    status = " . (int)$status . ",
                    last_updated = '" . _esc($today) . "'
                WHERE inventory_id = {$inventory_id}
                LIMIT 1
            ";

            $res = call_mysql_query($sql);
            if ($res) { echo "success"; exit(); }

            http_response_code(500);
            echo "Database update failed.";
            exit();
        }

        default: {
            http_response_code(400);
            echo "Unknown action.";
            exit();
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "Server error.";
    exit();
}