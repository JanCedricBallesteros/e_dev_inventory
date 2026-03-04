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

    // full code: CSM-{CAT}-0001 (CAT can be "0002" or "CSM0002")
    if (preg_match('/^CSM-([A-Za-z0-9\-_]+)-(\d{4})$/', $s, $m)) {
        $cat = (string)$m[1];
        // normalize "CSM0002" / "CSM-0002" -> "0002"
        if (preg_match('/^CSM-?(\d+)$/i', $cat, $mm)) $cat = (string)$mm[1];

        $digits = (string)intval($m[2], 10); // "0001" -> "1"
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
        'unit_quantity',
        'current_unit_quantity',
        'unit_crit_level',
        'item_cost',
        'source_of_funds'
    ]);

    fputcsv($out, [
        '1',
        'Example itemized description (details/specs/notes)',
        $exampleCat,
        '100',
        '80',
        '10',
        '25.50',
        'General Fund'
    ]);

    fclose($out);
    exit();
}

/**
 * EXPORT CSV (SERVER)
 * Optional filter: item_category_code
 * Note: acquisition_date is the add/import date, not included here (you can add it if you want)
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
            i.unit_quantity,
            i.current_unit_quantity,
            i.unit_crit_level,
            i.item_cost,
            i.source_of_funds
        FROM csm_inventory i
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
        'unit_quantity',
        'current_unit_quantity',
        'unit_crit_level',
        'item_cost',
        'source_of_funds'
    ]);

    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            fputcsv($out, [
                (string)($row['inventory_system_item_code'] ?? ''),
                (string)($row['item_description'] ?? ''),
                (string)($row['item_category_code'] ?? ''),
                (string)($row['unit_quantity'] ?? '0'),
                (string)($row['current_unit_quantity'] ?? '0'),
                (string)($row['unit_crit_level'] ?? '0'),
                (string)($row['item_cost'] ?? '0'),
                (string)($row['source_of_funds'] ?? ''),
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

// required columns (aligned to DB + your UI; acquisition_date is auto)
$required = [
    'inventory_system_item_code',
    'item_description',
    'item_category_code',
    'unit_quantity',
    'current_unit_quantity',
    'unit_crit_level',
    'item_cost'
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

$rowNum = 1;
$today = _today();

foreach ($rows as $r) {
    $rowNum++;

    $rawCode = trim((string)($r['inventory_system_item_code'] ?? ''));
    $item_description = trim((string)($r['item_description'] ?? ''));
    $cat = trim((string)($r['item_category_code'] ?? ''));

    $unit_quantity = (int)($r['unit_quantity'] ?? 0);
    $current_unit_quantity = (int)($r['current_unit_quantity'] ?? 0);
    $unit_crit_level = (int)($r['unit_crit_level'] ?? 0);

    $item_cost = (float)($r['item_cost'] ?? 0);
    $source_of_funds = trim((string)($r['source_of_funds'] ?? ''));

    if ($item_description === '' || $cat === '') {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Required fields missing (item_description / item_category_code).";
        $skipped++;
        continue;
    }

    if (!_category_exists($cat)) {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Unknown Category Code: {$cat}";
        $skipped++;
        continue;
    }

    $parsed = _parse_item_code_input($rawCode);
    if (!$parsed['ok']) {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: {$parsed['error']}";
        $skipped++;
        continue;
    }

    // If user provided FULL code, enforce category match (after normalization)
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

    $codeEsc = _esc($fullCode);
    $existsRes = call_mysql_query("SELECT inventory_id FROM csm_inventory WHERE inventory_system_item_code='{$codeEsc}' LIMIT 1");
    $existing = ($existsRes && ($er = call_mysql_fetch_array($existsRes))) ? $er : null;

    if ($existing) {
        if ($mode === 'insert_only') { $skipped++; continue; }

        $inventory_id = (int)$existing['inventory_id'];

        // Update does NOT change acquisition_date; status stays as-is
        $sql = "
            UPDATE csm_inventory
            SET
                item_description='" . _esc($item_description) . "',
                item_category_code='" . _esc($cat) . "',
                unit_quantity=" . (int)$unit_quantity . ",
                current_unit_quantity=" . (int)$current_unit_quantity . ",
                unit_crit_level=" . (int)$unit_crit_level . ",
                item_cost=" . (float)$item_cost . ",
                source_of_funds='" . _esc($source_of_funds) . "',
                last_updated='" . _esc($today) . "'
            WHERE inventory_id={$inventory_id}
            LIMIT 1
        ";

        $ok = call_mysql_query($sql);
        if ($ok) $updated++;
        else {
            if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Update failed for {$fullCode}";
            $skipped++;
        }
        continue;
    }

    // insert (acquisition_date = today, status = available)
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
            '" . _esc($fullCode) . "',
            '" . _esc($item_description) . "',
            '" . _esc($today) . "',
            " . (float)$item_cost . ",
            '" . _esc($source_of_funds) . "',
            '" . _esc($cat) . "',
            'available',
            " . (int)$unit_quantity . ",
            " . (int)$current_unit_quantity . ",
            " . (int)$unit_crit_level . ",
            '" . _esc($today) . "'
        )
    ";

    $ok = call_mysql_query($sql);
    if ($ok) $inserted++;
    else {
        if (count($errors) < $errorsLimit) $errors[] = "Row {$rowNum}: Insert failed for {$fullCode}";
        $skipped++;
    }
}

echo json_encode([
    'success' => true,
    'inserted' => $inserted,
    'updated' => $updated,
    'skipped' => $skipped,
    'errors_count' => count($errors),
    'errors' => $errors
]);
exit();
