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

    // remove spaces
    $compact = preg_replace('/\s+/', '', $s);

    // digits only (allow leading zeros)
    if (preg_match('/^\d+$/', $compact)) return $compact;

    // allow: CSM0001 / CSM-0001 / CSM_0001 / csm0001
    if (preg_match('/^CSM[-_]*\d+$/i', $compact)) {
        if (preg_match('/\d+$/', $compact, $m)) return (string)$m[0];
    }

    return '__INVALID__';
}

/**
 * Normalize category code to avoid: "CSM-CSM0002-0001"
 * If DB contains "CSM0002" or "CSM-0002", normalize to "0002"
 * Otherwise keep original (e.g. "CAT-001")
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
    // Normalize to CSM-... if it matches
    if (preg_match('/^CSM-([A-Za-z0-9\-_]+)-(\d{4})$/i', $c, $m)) {
        return "CSM-" . $m[1] . "-" . $m[2];
    }
    return $c;
}

/**
 * Resolve display_image (same logic you suggested)
 */
function _list_inventory_sql($where = "", $order = "ORDER BY i.created_at DESC", $limit = "") {
    $whereSql = $where ? "WHERE {$where}" : "";
    $limitSql = $limit ? "LIMIT {$limit}" : "";

    return "
        SELECT
            i.*,
            c.category_id AS category_id_ref,
            c.item_category_name,

            /* Resolved per-inventory assigned image (if item_category_img is numeric) */
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

            /* Final image for UI: assigned first, else category primary */
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
            $raw_code_numeric   = _post('inventory_system_item_code'); // numbers only or blank (also accepts CSM0001/CSM-0001)
            $item_description   = _post('item_description');
            $item_category_code = _post('item_category_code');

            $unit_quantity         = _int(_post('unit_quantity'));
            $current_unit_quantity = _int(_post('current_unit_quantity'));
            $unit_crit_level       = _int(_post('unit_crit_level'));

            $item_cost       = _float(_post('item_cost'));
            $source_of_funds = _post('source_of_funds');

            if ($item_description === '' || $item_category_code === '') {
                http_response_code(422);
                echo "Required fields missing (Itemized Description, Category).";
                exit();
            }
            if (!_category_exists($item_category_code)) {
                http_response_code(422);
                echo "Unknown Category Code: " . $item_category_code;
                exit();
            }
            if ($unit_quantity < 0 || $current_unit_quantity < 0 || $unit_crit_level < 0) {
                http_response_code(422);
                echo "Quantity/Critical level cannot be negative.";
                exit();
            }

            $digitsRaw = _only_digits_or_blank($raw_code_numeric);
            if ($digitsRaw === '__INVALID__') {
                http_response_code(422);
                echo "Item Code must be numbers only (or blank). You may also input CSM0001 / CSM-0001.";
                exit();
            }

            // normalize "0001" => "1"
            $digits = ($digitsRaw === '') ? '' : (string)intval($digitsRaw, 10);
            if ($digits !== '' && $digits === '0') {
                http_response_code(422);
                echo "Invalid Item Code number.";
                exit();
            }

            // Build FULL code: CSM-[category]-NNNN
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

            // uniqueness check
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

            $sql = "
                INSERT INTO csm_inventory
                (
                    inventory_system_item_code,
                    item_description,
                    acquisition_date,
                    item_cost,
                    source_of_funds,
                    item_category_code,
                    status,
                    unit_quantity,
                    current_unit_quantity,
                    unit_crit_level,
                    last_updated
                )
                VALUES
                (
                    '" . _esc($inventory_system_item_code) . "',
                    '" . _esc($item_description) . "',
                    '" . _esc($today) . "',
                    " . (float)$item_cost . ",
                    '" . _esc($source_of_funds) . "',
                    '" . _esc($item_category_code) . "',
                    'available',
                    " . (int)$unit_quantity . ",
                    " . (int)$current_unit_quantity . ",
                    " . (int)$unit_crit_level . ",
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

            // UPDATED: include display_image resolution
            $sql = _list_inventory_sql("", "ORDER BY i.created_at DESC", "");
            $result = call_mysql_query($sql);

            $items = [];
            if ($result) {
                while ($row = call_mysql_fetch_array($result)) $items[] = $row;
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
            if ($result) {
                while ($row = call_mysql_fetch_array($result)) $items[] = $row;
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

            echo json_encode(['success' => true, 'data' => $row]);
            exit();
        }

        // ============ UPDATE (MAIN RECORD, does NOT touch current_unit_quantity) ============
        case 'update_inventory': {
            $inventory_id = _int(_post('inventory_id'));
            if ($inventory_id <= 0) {
                http_response_code(422);
                echo "Invalid inventory_id.";
                exit();
            }

            $raw_code_numeric   = _post('inventory_system_item_code'); // numbers only or blank (also accepts CSM0001/CSM-0001)
            $item_description   = _post('item_description');
            $item_category_code = _post('item_category_code');

            $unit_quantity   = _int(_post('unit_quantity'));
            $unit_crit_level = _int(_post('unit_crit_level'));

            $item_cost       = _float(_post('item_cost'));
            $source_of_funds = _post('source_of_funds');

            if ($item_description === '' || $item_category_code === '') {
                http_response_code(422);
                echo "Required fields missing (Itemized Description, Category).";
                exit();
            }
            if (!_category_exists($item_category_code)) {
                http_response_code(422);
                echo "Unknown Category Code: " . $item_category_code;
                exit();
            }
            if ($unit_quantity < 0 || $unit_crit_level < 0) {
                http_response_code(422);
                echo "Quantity/Critical level cannot be negative.";
                exit();
            }

            $digitsRaw = _only_digits_or_blank($raw_code_numeric);
            if ($digitsRaw === '__INVALID__') {
                http_response_code(422);
                echo "Item Code must be numbers only (or blank). You may also input CSM0001 / CSM-0001.";
                exit();
            }

            // normalize "0001" => "1"
            $digits = ($digitsRaw === '') ? '' : (string)intval($digitsRaw, 10);
            if ($digits !== '' && $digits === '0') {
                http_response_code(422);
                echo "Invalid Item Code number.";
                exit();
            }

            // Build FULL code
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

            // prevent duplicates
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

            $today = _today();

            $sql = "
                UPDATE csm_inventory
                SET
                    inventory_system_item_code = '" . _esc($inventory_system_item_code) . "',
                    item_description = '" . _esc($item_description) . "',
                    item_category_code = '" . _esc($item_category_code) . "',
                    unit_quantity = " . (int)$unit_quantity . ",
                    unit_crit_level = " . (int)$unit_crit_level . ",
                    item_cost = " . (float)$item_cost . ",
                    source_of_funds = '" . _esc($source_of_funds) . "',
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

            $source_of_funds = _post('source_of_funds'); // optional; if blank => keep current
            $item_cost_raw   = _post('item_cost');       // optional; if blank => keep current

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

            // Fetch existing
            $q = call_mysql_query("SELECT unit_quantity, current_unit_quantity, unit_crit_level, source_of_funds, item_cost FROM csm_inventory WHERE inventory_id={$inventory_id} LIMIT 1");
            $row = $q ? call_mysql_fetch_array($q) : null;
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found.']);
                exit();
            }

            $old_unit_qty = (int)$row['unit_quantity'];
            $old_cur_qty  = (int)$row['current_unit_quantity'];

            $new_unit_qty = $old_unit_qty + $add_qty;
            $new_cur_qty  = $old_cur_qty + $add_qty;

            // keep existing if blank
            $final_source = ($source_of_funds === '') ? (string)$row['source_of_funds'] : $source_of_funds;

            $final_cost = (string)$row['item_cost'];
            if (trim($item_cost_raw) !== '') {
                $final_cost = (string)_float($item_cost_raw);
            }

            $today = _today();

            $sql = "
                UPDATE csm_inventory
                SET
                    unit_quantity = {$new_unit_qty},
                    current_unit_quantity = {$new_cur_qty},
                    source_of_funds = '" . _esc($final_source) . "',
                    item_cost = " . (float)$final_cost . ",
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

        // ============ UPDATE AVAILABLE ONLY (ENFORCE CRITICAL RULE) ============
        case 'update_available_qty': {
            $inventory_id = _int(_post('inventory_id'));
            $current_unit_quantity = _int(_post('current_unit_quantity'));

            if ($inventory_id <= 0) {
                http_response_code(422);
                echo "Invalid inventory_id.";
                exit();
            }
            if ($current_unit_quantity < 0) {
                http_response_code(422);
                echo "Available quantity cannot be negative.";
                exit();
            }

            // Enforce: cannot be less than critical level
            $chk = call_mysql_query("SELECT unit_crit_level, unit_quantity FROM csm_inventory WHERE inventory_id={$inventory_id} LIMIT 1");
            $r = $chk ? call_mysql_fetch_array($chk) : null;
            if (!$r) {
                http_response_code(404);
                echo "Record not found.";
                exit();
            }

            $crit = (int)$r['unit_crit_level'];
            $unitQty = (int)$r['unit_quantity'];

            if ($current_unit_quantity < $crit) {
                http_response_code(422);
                echo "Restriction: Available quantity cannot be less than Critical Level ({$crit}).";
                exit();
            }

            // Optional safety: cannot exceed total received
            if ($current_unit_quantity > $unitQty) {
                http_response_code(422);
                echo "Available quantity cannot exceed Actual Qty ({$unitQty}).";
                exit();
            }

            $today = _today();

            $sql = "
                UPDATE csm_inventory
                SET
                    current_unit_quantity = " . (int)$current_unit_quantity . ",
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