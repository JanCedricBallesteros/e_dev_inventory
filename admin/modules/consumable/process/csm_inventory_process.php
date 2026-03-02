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
$allowedRoles = ['SUPER_ADMIN', 'ADMIN', 'ADMINSTAFF'];
if (!isset($g_user_role) || !in_array($g_user_role, $allowedRoles, true)) {
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

            $sql = "
                SELECT
                    i.*,
                    c.item_category_name
                FROM csm_inventory i
                LEFT JOIN csm_inventory_category c
                    ON c.item_category_code = i.item_category_code
                ORDER BY i.created_at DESC
            ";

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

            $sql = "SELECT * FROM csm_inventory WHERE inventory_id = {$inventory_id} LIMIT 1";
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

        // ============ UPDATE AVAILABLE ONLY ============
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