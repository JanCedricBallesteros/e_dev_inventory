<?php
// csm_category_bulk_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$isStaffCsm = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("CSM", "PO")));
if (!(role_has("ADMIN") || $isStaffCsm)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Access denied.']);
    exit();
}

function _post($k, $default = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default; }
function _esc($v) {
    global $conn;
    $v = (string)$v;
    if (isset($conn) && $conn instanceof mysqli) return mysqli_real_escape_string($conn, $v);
    return addslashes($v);
}
function json_out($arr, $code = 200){
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit();
}

/**
 * Normalize category code into DB format.
 * - Numeric input still normalizes to legacy "CSM0001" style.
 * - Custom codes may be alphanumeric with "-" / "_".
 */
function normalize_category_code_db($raw, $minDigits = 4) {
    $raw = strtoupper(trim((string)$raw));
    if ($raw === '') return '';

    $raw = preg_replace('/\s+/', '', $raw);

    if (preg_match('/^CSM(\d+)$/', $raw, $m) || preg_match('/^(\d+)$/', $raw, $m)) {
        $digits = ltrim((string)$m[1], '0');
        if ($digits === '') return '';

        $minDigits = max(1, (int)$minDigits);
        if (strlen($digits) < $minDigits) {
            $digits = str_pad($digits, $minDigits, '0', STR_PAD_LEFT);
        }

        return 'CSM' . $digits;
    }

    if (preg_match('/^CSM[-_](.+)$/', $raw, $m)) {
        $raw = (string)$m[1];
    }

    if ($raw === '' || strpos($raw, 'CSM') === 0) return '';
    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $raw)) return '';

    return $raw;
}

/**
 * Extract numeric part as string (no leading zeros) for numeric comparisons.
 * - "CSM0001" => "1"
 * - "CSM10000" => "10000"
 * - "0001" => "1"
 */
function code_db_digits($dbCode) {
    $dbCode = strtoupper(trim((string)$dbCode));
    $dbCode = preg_replace('/\s+/', '', $dbCode);

    if (strpos($dbCode, 'CSM') === 0) {
        $digits = substr($dbCode, 3);
    } else {
        $digits = $dbCode;
    }

    if ($digits === '' || !ctype_digit($digits)) return '';

    $digits = ltrim($digits, '0');
    return ($digits === '') ? '' : $digits;
}

function normalize_catcode_php($code) {
    return strtoupper(preg_replace('/\s+/', '', trim((string)$code)));
}

function is_legacy_numeric_category_code($code) {
    $code = normalize_catcode_php($code);
    return ($code !== '' && preg_match('/^CSM\d+$/', $code));
}

/** Concurrency-safe lock */
function acquire_code_lock($timeoutSeconds = 8) {
    $timeoutSeconds = (int)$timeoutSeconds;
    $res = call_mysql_query("SELECT GET_LOCK('csm_inventory_category_code_lock', {$timeoutSeconds}) AS l");
    $row = $res ? call_mysql_fetch_array($res) : null;
    return (int)($row['l'] ?? 0) === 1;
}
function release_code_lock() { @call_mysql_query("SELECT RELEASE_LOCK('csm_inventory_category_code_lock')"); }

/**
 * Must be called only while holding the lock.
 * Returns next DB code "CSM0001" (or higher, no max limit).
 *
 * Finds max across:
 * - "CSM0001" / "CSM-0001" / "CSM10000"
 * - legacy digits-only "0001" / "1"
 */
function get_next_category_code_db_locked() {
    $res = call_mysql_query("SELECT item_category_code FROM csm_inventory_category");
    $max = 0;

    if ($res) {
        while ($row = call_mysql_fetch_array($res)) {
            $digits = code_db_digits($row['item_category_code'] ?? '');
            if ($digits === '') continue;
            $n = (int)$digits;
            if ($n > $max) $max = $n;
        }
    }

    $next = $max + 1;
    if ($next <= 0) return '';
    return normalize_category_code_db((string)$next);
}

/**
 * Robust duplicate check for a normalized DB code.
 * Matches existing records even if stored as:
 * - "CSM0001"
 * - "CSM-0001"
 * - "0001" / "1" (legacy)
 */
function code_exists_any_format($dbCode) {
    $normalized = normalize_category_code_db($dbCode);
    if ($normalized === '') return false;

    if (is_legacy_numeric_category_code($normalized)) {
        $digits = code_db_digits($normalized);
        if ($digits === '') return false;

        $normEsc = _esc(strtoupper(preg_replace('/[\s\-]/', '', $normalized)));
        $digitsEsc = _esc($digits);

        $sql = "
          SELECT category_id
          FROM csm_inventory_category
          WHERE
            REPLACE(REPLACE(UPPER(TRIM(item_category_code)), ' ', ''), '-', '') = '{$normEsc}'
            OR
            (
              TRIM(item_category_code) REGEXP '^[0-9]+$'
              AND CAST(TRIM(item_category_code) AS UNSIGNED) = CAST('{$digitsEsc}' AS UNSIGNED)
            )
          LIMIT 1
        ";
    } else {
        $normEsc = _esc(normalize_catcode_php($normalized));
        $sql = "
          SELECT category_id
          FROM csm_inventory_category
          WHERE UPPER(REPLACE(TRIM(item_category_code), ' ', '')) = '{$normEsc}'
          LIMIT 1
        ";
    }

    $res = call_mysql_query($sql);
    return ($res && call_mysql_fetch_array($res)) ? true : false;
}

/** Read CSV */
function csv_read_rows($path) {
    $rows = [];
    $fh = @fopen($path, 'r');
    if (!$fh) return $rows;

    while (($data = fgetcsv($fh)) !== false) {
        if (!is_array($data)) continue;

        // skip fully empty rows
        $allEmpty = true;
        foreach ($data as $c) {
            if (trim((string)$c) !== '') { $allEmpty = false; break; }
        }
        if ($allEmpty) continue;

        $rows[] = $data;
    }
    fclose($fh);
    return $rows;
}

$action = _post('action', '');
if ($action === '') json_out(['success'=>false,'message'=>'Missing action.'], 400);

try {
    switch ($action) {

        case 'download_template': {
            $filename = 'csm_category_template.csv';
            $content =
                "Category Name,Code\n" .
                "Cleaning Supplies,\n" .
                "Electrical,1\n" .
                "Plumbing,CSM0003\n" .
                "Office Supplies,OFFICE\n" .
                "Chemistry Set,CHEM-01\n";
            json_out(['success'=>true,'filename'=>$filename,'content'=>$content]);
        }

        case 'bulk_add_category': {
            if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                json_out(['success'=>false,'message'=>'No CSV uploaded.'], 422);
            }

            $tmp = $_FILES['file']['tmp_name'];
            if (!is_uploaded_file($tmp)) {
                json_out(['success'=>false,'message'=>'Invalid upload.'], 422);
            }

            $rows = csv_read_rows($tmp);
            if (!$rows) {
                json_out(['success'=>false,'message'=>'CSV is empty or unreadable.'], 422);
            }

            // detect header row
            $first = $rows[0];
            $h0 = strtolower(trim((string)($first[0] ?? '')));
            if ($h0 === 'category name' || $h0 === 'category_name' || $h0 === 'name') {
                array_shift($rows);
            }

            if (!acquire_code_lock(8)) {
                json_out(['success'=>false,'message'=>'Code generator busy. Try again.'], 409);
            }

            $inserted = 0;
            $skipped  = 0;
            $errors   = [];
            $insertedNames = [];
            $insertedCodes = [];
            $skippedEntries = [];

            // CSV internal duplicate protection
            $seenNames = [];
            $seenCodes = [];

            $rowIndex = 1; // since header may be removed, we still report 1-based row numbers
            foreach ($rows as $r) {
                $rowIndex++;

                $name = trim((string)($r[0] ?? ''));
                $codeRaw = trim((string)($r[1] ?? ''));

                if ($name === '') {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Missing Category Name.";
                    $skippedEntries[] = '(missing name)';
                    continue;
                }

                // Normalize name for CSV dup detection (trim + collapse spaces + lower)
                $nameNorm = preg_replace('/\s+/', ' ', trim($name));
                $nameKey = mb_strtolower($nameNorm);
                if (isset($seenNames[$nameKey])) {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Duplicate name in CSV: {$nameNorm}";
                    $skippedEntries[] = $nameNorm . ' (duplicate_name)';
                    continue;
                }
                $seenNames[$nameKey] = true;

                // Normalize / generate code
                if ($codeRaw !== '') {
                    $dbCode = normalize_category_code_db($codeRaw);
                    if ($dbCode === '') {
                        $skipped++;
                        $errors[] = "Row {$rowIndex}: Invalid Code '{$codeRaw}'. Use letters, numbers, '-' or '_' or leave blank.";
                        $skippedEntries[] = $nameNorm . ' [' . $codeRaw . '] (invalid_code)';
                        continue;
                    }
                } else {
                    $dbCode = get_next_category_code_db_locked();
                    if ($dbCode === '') {
                        $skipped++;
                        $errors[] = "Row {$rowIndex}: Failed to generate next code.";
                        $skippedEntries[] = $nameNorm . ' (generate_code_failed)';
                        continue;
                    }
                }

                // CSV duplicate code detection (normalized)
                $dbCodeKey = is_legacy_numeric_category_code($dbCode)
                    ? strtoupper(preg_replace('/[\s\-]/', '', $dbCode))
                    : normalize_catcode_php($dbCode);
                if (isset($seenCodes[$dbCodeKey])) {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Duplicate code in CSV: {$dbCode}";
                    $skippedEntries[] = $nameNorm . ' [' . $dbCode . '] (duplicate_code)';
                    continue;
                }
                $seenCodes[$dbCodeKey] = true;

                // DB duplicate code detection (robust across formats)
                if (code_exists_any_format($dbCode)) {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Code already exists: {$dbCode}";
                    $skippedEntries[] = $nameNorm . ' [' . $dbCode . '] (duplicate_code)';
                    continue;
                }

                // DB duplicate name (case-insensitive; trim)
                $chkName = call_mysql_query("
                    SELECT category_id
                    FROM csm_inventory_category
                    WHERE LOWER(TRIM(item_category_name)) = LOWER('"._esc($nameNorm)."')
                    LIMIT 1
                ");
                if ($chkName && call_mysql_fetch_array($chkName)) {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Name already exists: {$nameNorm}";
                    $skippedEntries[] = $nameNorm . ' [' . $dbCode . '] (duplicate_name)';
                    continue;
                }

                // INSERT (DB code normalized)
                $ok = call_mysql_query("
                    INSERT INTO csm_inventory_category (item_category_name, item_category_code)
                    VALUES ('"._esc($nameNorm)."','"._esc($dbCode)."')
                ");
                if ($ok) {
                    $inserted++;
                    $insertedNames[] = $nameNorm;
                    $insertedCodes[] = $dbCode;
                } else {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Insert failed for {$nameNorm} ({$dbCode}).";
                    $skippedEntries[] = $nameNorm . ' [' . $dbCode . '] (insert_failed)';
                }
            }

            release_code_lock();

            if (count($errors) > 20) {
                $errors = array_slice($errors, 0, 20);
                $errors[] = "More issues exist (showing up to 20).";
            }

            $logDetails = array(
                'inserted' => $inserted,
                'skipped' => $skipped
            );
            if (!empty($insertedNames)) {
                $logDetails['added_names'] = $insertedNames;
            }
            if (!empty($insertedCodes)) {
                $logDetails['added_codes'] = $insertedCodes;
            }
            if (!empty($skippedEntries)) {
                $logDetails['not_added'] = $skippedEntries;
            }
            activity_log_new("CSM CATEGORY BULK IMPORT", "SUCCESS", $logDetails);

            json_out([
                'success'  => true,
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ]);
        }

        default: {
            json_out(['success'=>false,'message'=>'Unknown action.'], 400);
        }
    }

} catch (Throwable $e) {
    release_code_lock();
    json_out(['success'=>false,'message'=>'Server error: '.$e->getMessage()], 500);
}
