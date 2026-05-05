<?php
// process/csm_inventory_bulk_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// -------------------- ACCESS CONTROL --------------------
$isStaffCsm = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("CSM", "PO")));
if (!(role_has("ADMIN") || $isStaffCsm)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit();
}

// -------------------- HELPERS --------------------
function _esc($v) {
    global $conn;
    $v = (string)$v;
    if (isset($conn) && $conn instanceof mysqli) return mysqli_real_escape_string($conn, $v);
    return addslashes($v);
}
function _today() { return date('Y-m-d'); }

/**
 * Accepts:
 * - blank => ''
 * - numbers only => '1'
 * - full code => 'CSM-0002-0001' or 'CSM-CSM0002-0001' (we normalize cat part)
 *
 * Returns array:
 *  [
 *    'ok' => bool,
 *    'type' => 'blank'|'digits'|'full',
 *    'digits' => ''|'1',
 *    'cat_from_code' => ''|'0002',
 *    'error' => ''|string
 *  ]
 */
function _parse_item_code_input($raw) {
    $s = trim((string)$raw);
    if ($s === '') return ['ok'=>true, 'type'=>'blank', 'digits'=>'', 'cat_from_code'=>'', 'error'=>''];

    if (preg_match('/^\d+$/', $s)) {
        return ['ok'=>true, 'type'=>'digits', 'digits'=>$s, 'cat_from_code'=>'', 'error'=>''];
    }

    if (preg_match('/^CSM-([A-Za-z0-9\-_]+)-(\d{4})$/', $s, $m)) {
        $cat = (string)$m[1];
        if (preg_match('/^CSM-?(\d+)$/i', $cat, $mm)) $cat = (string)$mm[1];

        $digits = (string)intval($m[2], 10);
        if ($digits === '0') $digits = '';
        if ($digits === '') {
            return ['ok'=>false, 'type'=>'full', 'digits'=>'', 'cat_from_code'=>$cat, 'error'=>"Invalid numeric suffix in item code: {$s}"];
        }
        return ['ok'=>true, 'type'=>'full', 'digits'=>$digits, 'cat_from_code'=>$cat, 'error'=>''];
    }

    return ['ok'=>false, 'type'=>'unknown', 'digits'=>'', 'cat_from_code'=>'', 'error'=>"Item Code must be numbers only, blank, or full code like CSM-0002-0001. Got: {$s}"];
}

function _category_exists($catCode) {
    $catCode = trim((string)$catCode);
    if ($catCode === '') return false;
    $catEsc = _esc($catCode);
    $sql = "SELECT item_category_code FROM csm_inventory_category WHERE item_category_code='{$catEsc}' LIMIT 1";
    $res = call_mysql_query($sql);
    return ($res && call_mysql_fetch_array($res)) ? true : false;
}

function _normalize_category_code_for_itemcode($catCode) {
    $catCode = trim((string)$catCode);
    if ($catCode === '') return '';
    if (preg_match('/^CSM-?(\d+)$/i', $catCode, $m)) return (string)$m[1];
    return $catCode;
}

function _format_item_code($catCode, $numStr) {
    $catCode = _normalize_category_code_for_itemcode($catCode);
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
    return _format_item_code($catCode, (string)_next_number_for_category($catCode));
}

function _detect_delimiter($line) {
    $delims = [",", ";", "\t", "|"];
    $best = ",";
    $bestCount = -1;
    foreach ($delims as $d) {
        $c = substr_count($line, $d);
        if ($c > $bestCount) { $bestCount = $c; $best = $d; }
    }
    return $best;
}

function _normalize_header($h) {
    $h = strtolower(trim((string)$h));
    $h = preg_replace('/\s+/', '_', $h);
    return $h;
}

function _read_csv_rows($tmpPath) {
    $fh = fopen($tmpPath, 'rb');
    if (!$fh) return [null, null];

    $firstLine = fgets($fh);
    if ($firstLine === false) { fclose($fh); return [null, null]; }
    $delimiter = _detect_delimiter($firstLine);
    rewind($fh);

    $header = fgetcsv($fh, 0, $delimiter);
    if (!$header) { fclose($fh); return [null, null]; }
    $header = array_map('_normalize_header', $header);

    $rows = [];
    while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
        if (count($data) === 1 && trim((string)$data[0]) === '') continue;
        $row = [];
        for ($i=0; $i<count($header); $i++) {
            $key = $header[$i] ?? "col_$i";
            $row[$key] = isset($data[$i]) ? trim((string)$data[$i]) : '';
        }
        $rows[] = $row;
    }
    fclose($fh);
    return [$header, $rows];
}

// -------------------- RULE / STATUS HELPERS --------------------
function normalize_allowed_payload_csm_bulk($raw) {
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

function normalize_allowed_employment_csm_bulk($raw) {
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

/*
0 = Unavailable
1 = Available
2 = Stock Critical
3 = Out of Stock
*/
function _compute_status_from_state_bulk($currentQty, $critLevel, $allowedRaw) {
    $currentQty = (int)$currentQty;
    $critLevel  = (int)$critLevel;

    $norm = normalize_allowed_employment_csm_bulk($allowedRaw);
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

// -------------------- ROUTING --------------------
$action = isset($_REQUEST['action']) ? trim((string)$_REQUEST['action']) : '';

if ($action === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="csm_inventory_bulk_template.csv"');

    $exampleCat = '';
    $res = call_mysql_query("SELECT item_category_code FROM csm_inventory_category ORDER BY item_category_code ASC LIMIT 1");
    if ($res && ($r = call_mysql_fetch_array($res)) && !empty($r['item_category_code'])) {
        $exampleCat = $r['item_category_code'];
    }

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'inventory_system_item_code',
        'item_description',
        'item_category_code',
        'cost_value',
        'unit',
        'quantity',
        'current_quantity',
        'qty_crit_level',
        'source_of_funds',
        'status',
        'allowed_employment_status'
    ]);

    fputcsv($out, [
        '1',
        'Example itemized description (details/specs/notes)',
        $exampleCat,
        '25.50',
        'pcs',
        '100',
        '80',
        '10',
        'General Fund',
        '1',
        'ALL'
    ]);

    fclose($out);
    exit();
}

/**
 * EXPORT CSV (SERVER)
 * Optional filter: item_category_code
 */
if ($action === 'export_csv') {
    $cat = isset($_GET['item_category_code']) ? trim((string)$_GET['item_category_code']) : '';

    $where = [];
    if ($cat !== '') $where[] = "i.item_category_code='" . _esc($cat) . "'";

    $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "
        SELECT
            i.inventory_system_item_code,
            i.item_description,
            i.item_category_code,
            c.item_category_name,
            i.cost_value,
            i.unit,
            i.quantity,
            i.current_quantity,
            i.qty_crit_level,
            i.source_of_funds,
            i.status,
            i.allowed_employment_status
        FROM csm_inventory i
        LEFT JOIN csm_inventory_category c
            ON c.item_category_code = i.item_category_code
        {$whereSql}
        ORDER BY i.created_at DESC
    ";
    $res = call_mysql_query($sql);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="csm_inventory_export.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'inventory_system_item_code',
        'item_description',
        'item_category_code',
        'item_category_name',
        'cost_value',
        'unit',
        'quantity',
        'current_quantity',
        'qty_crit_level',
        'source_of_funds',
        'status',
        'allowed_employment_status'
    ]);

    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            $allowed = $row['allowed_employment_status'];
            if ($allowed === null || $allowed === '') $allowed = 'ALL';

            fputcsv($out, [
                (string)($row['inventory_system_item_code'] ?? ''),
                (string)($row['item_description'] ?? ''),
                (string)($row['item_category_code'] ?? ''),
                (string)($row['item_category_name'] ?? ''),
                (string)($row['cost_value'] ?? '0'),
                (string)($row['unit'] ?? ''),
                (string)($row['quantity'] ?? '0'),
                (string)($row['current_quantity'] ?? '0'),
                (string)($row['qty_crit_level'] ?? '0'),
                (string)($row['source_of_funds'] ?? ''),
                (string)($row['status'] ?? '0'),
                (string)$allowed
            ]);
        }
    }

    fclose($out);
    exit();
}

if ($action !== 'bulk_import') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}

// -------------------- BULK IMPORT --------------------
header('Content-Type: application/json; charset=utf-8');

$mode = isset($_POST['mode']) ? trim((string)$_POST['mode']) : 'upsert';
if ($mode !== 'upsert' && $mode !== 'insert_only') $mode = 'upsert';

if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No CSV file uploaded.']);
    exit();
}

$tmpPath = $_FILES['csv_file']['tmp_name'];
list($header, $rows) = _read_csv_rows($tmpPath);

if (!$header || !$rows) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'CSV is empty or invalid.']);
    exit();
}

// required columns
$required = [
    'inventory_system_item_code',
    'item_description',
    'item_category_code',
    'cost_value',
    'unit',
    'quantity',
    'current_quantity',
    'qty_crit_level'
];
foreach ($required as $req) {
    if (!in_array($req, $header, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing required column: {$req}"]);
        exit();
    }
}

$inserted = 0;
$updated = 0;
$skipped = 0;
$errors = [];
$errorsLimit = 20;
$insertedCodes = [];
$updatedCodes = [];

$rowNum = 1;
$today = _today();

foreach ($rows as $r) {
    $rowNum++;

    $rawCode = trim((string)($r['inventory_system_item_code'] ?? ''));
    $item_description = trim((string)($r['item_description'] ?? ''));
    $cat = trim((string)($r['item_category_code'] ?? ''));

    $cost_value = (float)($r['cost_value'] ?? 0);
    $unit = trim((string)($r['unit'] ?? ''));

    $quantity = (int)($r['quantity'] ?? 0);
    $current_quantity = (int)($r['current_quantity'] ?? 0);
    $qty_crit_level = (int)($r['qty_crit_level'] ?? 0);

    $source_of_funds = trim((string)($r['source_of_funds'] ?? ''));
    $statusCsvRaw = trim((string)($r['status'] ?? ''));
    $allowedRaw = trim((string)($r['allowed_employment_status'] ?? ''));

    if ($item_description === '' || $cat === '' || $unit === '') {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Required fields missing (item_description / item_category_code / unit).";
        $skipped++;
        continue;
    }

    if (!_category_exists($cat)) {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Unknown Category Code: {$cat}";
        $skipped++;
        continue;
    }

    if ($cost_value < 0 || $quantity < 0 || $current_quantity < 0 || $qty_crit_level < 0) {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Cost, quantities, and critical level cannot be negative.";
        $skipped++;
        continue;
    }

    if ($current_quantity > $quantity) {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: current_quantity cannot exceed quantity.";
        $skipped++;
        continue;
    }

    $parsed = _parse_item_code_input($rawCode);
    if (!$parsed['ok']) {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: {$parsed['error']}";
        $skipped++;
        continue;
    }

    // If user provided FULL code, enforce category match
    if ($parsed['type'] === 'full') {
        $catFromCode = $parsed['cat_from_code'];
        $catNorm = _normalize_category_code_for_itemcode($cat);
        if ($catFromCode !== '' && strcasecmp($catFromCode, $catNorm) !== 0) {
            if (count($errors) < $errorsLimit) {
                $errors[] = "Row {$rowNum}: Item Code category mismatch. Code has '{$catFromCode}' but item_category_code is '{$cat}'.";
            }
            $skipped++;
            continue;
        }
    }

    // Build FULL code EXACTLY
    if ($parsed['type'] === 'blank') {
        $fullCode = _generate_item_code_auto($cat);
    } else {
        $digits = $parsed['digits'];
        $fullCode = _format_item_code($cat, $digits);
        if ($fullCode === '') {
            if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Invalid Item Code number: {$rawCode}";
            $skipped++;
            continue;
        }
    }

    $allowedNorm = normalize_allowed_payload_csm_bulk($allowedRaw);
    $allowed_json = $allowedNorm['json'];

    if ($statusCsvRaw !== '' && !in_array($statusCsvRaw, ['0', '1', '2', '3'], true)) {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: status must be 0, 1, 2, or 3 when provided.";
        $skipped++;
        continue;
    }

    // Final status is auto-computed from qty + crit + rules
    $finalStatus = _compute_status_from_state_bulk($current_quantity, $qty_crit_level, $allowed_json);

    $codeEsc = _esc($fullCode);
    $existsRes = call_mysql_query("SELECT inventory_id FROM csm_inventory WHERE inventory_system_item_code='{$codeEsc}' LIMIT 1");
    $existing = ($existsRes && ($er = call_mysql_fetch_array($existsRes))) ? $er : null;

    if ($existing) {
        if ($mode === 'insert_only') { $skipped++; continue; }

        $inventory_id = (int)$existing['inventory_id'];

        $sql = "
            UPDATE csm_inventory
            SET
                item_description='" . _esc($item_description) . "',
                item_category_code='" . _esc($cat) . "',
                cost_value=" . (float)$cost_value . ",
                unit='" . _esc($unit) . "',
                quantity=" . (int)$quantity . ",
                current_quantity=" . (int)$current_quantity . ",
                qty_crit_level=" . (int)$qty_crit_level . ",
                source_of_funds='" . _esc($source_of_funds) . "',
                status=" . (int)$finalStatus . ",
                allowed_employment_status=" . ($allowed_json ? "'" . _esc($allowed_json) . "'" : "NULL") . ",
                last_updated='" . _esc($today) . "'
            WHERE inventory_id={$inventory_id}
            LIMIT 1
        ";

        $ok = call_mysql_query($sql);
        if ($ok) {
            $updated++;
            $updatedCodes[] = $fullCode;
        }
        else {
            if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Update failed for {$fullCode}";
            $skipped++;
        }
        continue;
    }

    // insert
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
            '" . _esc($fullCode) . "',
            '" . _esc($item_description) . "',
            '" . _esc($today) . "',
            " . (float)$cost_value . ",
            '" . _esc($unit) . "',
            '" . _esc($source_of_funds) . "',
            '" . _esc($cat) . "',
            " . (int)$finalStatus . ",
            " . (int)$quantity . ",
            " . (int)$current_quantity . ",
            " . (int)$qty_crit_level . ",
            " . ($allowed_json ? "'" . _esc($allowed_json) . "'" : "NULL") . ",
            '" . _esc($today) . "'
        )
    ";

    $ok = call_mysql_query($sql);
    if ($ok) {
        $inserted++;
        $insertedCodes[] = $fullCode;
    }
    else {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Insert failed for {$fullCode}";
        $skipped++;
    }
}

activity_log_new("CSM BULK IMPORT INVENTORY", "SUCCESS", array(
    'mode' => $mode,
    'inserted' => $inserted,
    'updated' => $updated,
    'skipped' => $skipped,
    'inserted_codes' => $insertedCodes,
    'updated_codes' => $updatedCodes,
    'errors_count' => count($errors)
));

echo json_encode([
    'success' => true,
    'inserted' => $inserted,
    'updated' => $updated,
    'skipped' => $skipped,
    'errors_count' => count($errors),
    'errors' => $errors
]);
exit();
