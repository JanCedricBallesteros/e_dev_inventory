<?php
// csm_category_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$allowedRoles = ["ADMIN", "ADMIN_STAFF", "ADMINSTAFF"];
if (!isset($g_user_role) || !in_array($g_user_role, $allowedRoles, true)) {
    http_response_code(403);
    echo "Access denied.";
    exit();
}

function _post($k, $default = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default; }
function _int($v, $default = 0) { if ($v === '' || $v === null) return $default; return (int)$v; }
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
 * Normalize any provided code into DB format:
 * - Always returns "CSM" + digits (variable length)
 * - Pads to at least 4 digits (1 => CSM0001)
 * - Keeps longer numbers (10000 => CSM10000)
 *
 * Accepts examples:
 * - "CSM0001", "csm0001", "CSM-0001", "CSM 0001"
 * - "0001", "1"
 * - "CSM10000", "CSM-00000025"
 *
 * Returns '' if invalid (no digits, non-digits, or all zeros).
 */
function normalize_category_code_db($raw, $minDigits = 4) {
    $raw = strtoupper(trim((string)$raw));
    if ($raw === '') return '';

    // Remove spaces and hyphens
    $raw = preg_replace('/[\s\-]/', '', $raw);

    // Strip prefix if present
    if (strpos($raw, 'CSM') === 0) {
        $raw = substr($raw, 3);
    }

    // Must now be digits only
    if ($raw === '' || !ctype_digit($raw)) return '';

    // Remove leading zeros but keep at least one digit
    $digits = ltrim($raw, '0');
    if ($digits === '') return ''; // all zeros not allowed

    $minDigits = max(1, (int)$minDigits);
    if (strlen($digits) < $minDigits) {
        $digits = str_pad($digits, $minDigits, '0', STR_PAD_LEFT);
    }

    return 'CSM' . $digits;
}

/**
 * Normalize any stored code for comparison (removes dash/space, upper).
 * "CSM-0001" -> "CSM0001"
 */
function normalize_catcode_php($code) {
    $code = strtoupper(trim((string)$code));
    $code = str_replace([' ', '-'], '', $code);
    return $code;
}

/**
 * Extract numeric part as string (no leading zeros) for numeric comparisons.
 * - "CSM0001" => "1"
 * - "CSM10000" => "10000"
 * - "0001" (legacy) => "1"
 */
function code_db_digits($dbCode) {
    $dbCode = normalize_catcode_php($dbCode);

    if (strpos($dbCode, 'CSM') === 0) {
        $digits = substr($dbCode, 3);
    } else {
        $digits = $dbCode;
    }

    if ($digits === '' || !ctype_digit($digits)) return '';

    $digits = ltrim($digits, '0');
    return ($digits === '') ? '' : $digits;
}

/** Concurrency-safe lock */
function acquire_code_lock($timeoutSeconds = 5) {
    $timeoutSeconds = (int)$timeoutSeconds;
    $res = call_mysql_query("SELECT GET_LOCK('csm_inventory_category_code_lock', {$timeoutSeconds}) AS l");
    $row = $res ? call_mysql_fetch_array($res) : null;
    return (int)($row['l'] ?? 0) === 1;
}
function release_code_lock() { @call_mysql_query("SELECT RELEASE_LOCK('csm_inventory_category_code_lock')"); }

/**
 * Must be called only while holding the lock.
 * Finds max across CSM####... / CSM-####... / digits-only legacy.
 * Returns next digits for UI:
 * - padded to at least 4 for display ("0001", "0012")
 * - longer stays longer ("10000")
 */
function get_next_category_digits_locked() {
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

    $nextStr = (string)$next;

    // UI wants at least 4 digits for "CSM-0001"
    if (strlen($nextStr) < 4) {
        $nextStr = str_pad($nextStr, 4, '0', STR_PAD_LEFT);
    }
    return $nextStr;
}

/**
 * Robust code existence check (matches DB even if code stored as legacy digits-only).
 * Input expects DB-normalized "CSM....".
 */
function category_code_exists_any_format($dbCode) {
    $dbCodeNorm = normalize_catcode_php($dbCode); // CSM...
    if ($dbCodeNorm === '' || strpos($dbCodeNorm, 'CSM') !== 0) return false;

    $digits = code_db_digits($dbCodeNorm); // "1" / "10000"
    if ($digits === '') return false;

    $dbCodeEsc = _esc($dbCodeNorm);
    $digitsEsc = _esc($digits);

    $sql = "
      SELECT category_id
      FROM csm_inventory_category
      WHERE
        REPLACE(REPLACE(UPPER(TRIM(item_category_code)), ' ', ''), '-', '') = '{$dbCodeEsc}'
        OR
        (
          TRIM(item_category_code) REGEXP '^[0-9]+$'
          AND CAST(TRIM(item_category_code) AS UNSIGNED) = CAST('{$digitsEsc}' AS UNSIGNED)
        )
      LIMIT 1
    ";
    $res = call_mysql_query($sql);
    return ($res && call_mysql_fetch_array($res)) ? true : false;
}

$action = _post('action', '');
if ($action === '') {
    http_response_code(400);
    echo "Missing action.";
    exit();
}

$UPLOAD_REL_DIR = 'upload/category/';
$UPLOAD_ABS_DIR = dirname(__DIR__, 4) . '/' . $UPLOAD_REL_DIR;
if (!is_dir($UPLOAD_ABS_DIR)) { @mkdir($UPLOAD_ABS_DIR, 0777, true); }

try {
    switch ($action) {

        /* ===================== CATEGORY CODE ===================== */

        case 'get_next_code': {
            if (!acquire_code_lock(5)) {
                json_out(['success'=>false,'message'=>'Code generator busy. Try again.'], 409);
            }

            $digits = get_next_category_digits_locked();
            release_code_lock();

            if ($digits === '') json_out(['success'=>false,'message'=>'Unable to generate next code.'], 409);

            // UI shows "CSM-0001" (or "CSM-10000")
            json_out(['success'=>true,'digits'=>$digits,'full'=>'CSM-'.$digits]);
        }

        /* ===================== CATEGORY CRUD ===================== */

        case 'add_category': {
            header('Content-Type: text/plain; charset=utf-8');

            $item_category_name = _post('item_category_name');
            if ($item_category_name === '') {
                http_response_code(422);
                echo "Required field missing (Category Name).";
                exit();
            }
            $item_category_name = preg_replace('/\s+/', ' ', trim($item_category_name));

            /**
             * UPDATED:
             * UI can submit digits-only OR full code variants.
             * Whatever comes in, we store DB-normalized "CSM....".
             */
            $rawInputCode = _post('item_category_code', '');
            $providedDbCode = ($rawInputCode === '') ? '' : normalize_category_code_db($rawInputCode);

            if ($rawInputCode !== '' && $providedDbCode === '') {
                http_response_code(422);
                echo "Invalid Category Code. Use digits or CSM formats (e.g., 1 / 0001 / CSM0001 / CSM-0001) or leave blank.";
                exit();
            }

            if (!acquire_code_lock(5)) {
                http_response_code(409);
                echo "Code generator busy. Please try again.";
                exit();
            }

            // Decide final code
            if ($providedDbCode === '') {
                $finalDigitsUI = get_next_category_digits_locked();
                if ($finalDigitsUI === '') {
                    release_code_lock();
                    http_response_code(409);
                    echo "Unable to generate next code.";
                    exit();
                }

                // Convert UI digits to DB "CSM...." (variable length allowed)
                // If UI digits are "0001" => normalize => CSM0001
                // If UI digits are "10000" => normalize => CSM10000
                $finalDbCode = normalize_category_code_db($finalDigitsUI);
                if ($finalDbCode === '') {
                    release_code_lock();
                    http_response_code(500);
                    echo "Failed to normalize generated code.";
                    exit();
                }
            } else {
                $finalDbCode = normalize_catcode_php($providedDbCode);

                if (category_code_exists_any_format($finalDbCode)) {
                    release_code_lock();
                    http_response_code(409);
                    echo "Category Code already exists.";
                    exit();
                }
            }

            // Name duplicate (case-insensitive)
            $chkName = call_mysql_query("
                SELECT category_id
                FROM csm_inventory_category
                WHERE LOWER(TRIM(item_category_name)) = LOWER('"._esc($item_category_name)."')
                LIMIT 1
            ");
            if ($chkName && call_mysql_fetch_array($chkName)) {
                release_code_lock();
                http_response_code(409);
                echo "Category Name already exists.";
                exit();
            }

            // Insert DB code "CSM...."
            $ins = "INSERT INTO csm_inventory_category (item_category_name, item_category_code)
                    VALUES ('"._esc($item_category_name)."','"._esc($finalDbCode)."')";
            $ok = call_mysql_query($ins);

            release_code_lock();

            if (!$ok) {
                http_response_code(500);
                echo "Database insert failed.";
                exit();
            }

            // Resolve inserted ID
            $category_id = 0;
            global $conn;
            if (isset($conn) && $conn instanceof mysqli && $conn->insert_id) {
                $category_id = (int)$conn->insert_id;
            } else {
                $rid = call_mysql_query("SELECT LAST_INSERT_ID() AS id");
                if ($rid && ($row = call_mysql_fetch_array($rid))) $category_id = (int)($row['id'] ?? 0);
            }
            if ($category_id <= 0) {
                http_response_code(500);
                echo "Failed to resolve new category id.";
                exit();
            }

            // optional images upload
            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $names = $_FILES['images']['name'];
                $tmps  = $_FILES['images']['tmp_name'];
                $errs  = $_FILES['images']['error'];
                $sizes = $_FILES['images']['size'];

                $allowedExt = ['jpg','jpeg','png','webp','gif'];
                $maxBytes = 5 * 1024 * 1024;

                $insertedImg = 0;

                for ($i = 0; $i < count($names); $i++) {
                    if (!isset($errs[$i]) || $errs[$i] !== UPLOAD_ERR_OK) continue;
                    if (!isset($tmps[$i]) || !is_uploaded_file($tmps[$i])) continue;

                    $orig = (string)$names[$i];
                    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) continue;

                    $sz = (int)($sizes[$i] ?? 0);
                    if ($sz <= 0 || $sz > $maxBytes) continue;

                    $imgInfo = @getimagesize($tmps[$i]);
                    if ($imgInfo === false) continue;

                    $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
                    $rand = bin2hex(random_bytes(6));
                    $newName = 'cat_' . $category_id . '_' . date('YmdHis') . '_' . $rand . '_' . $i . '_' . $safeBase . '.' . $ext;

                    if (!move_uploaded_file($tmps[$i], $UPLOAD_ABS_DIR . $newName)) continue;

                    $isPrimary = ($insertedImg === 0) ? 1 : 0;
                    $fileUrl = $UPLOAD_REL_DIR . $newName; // NOT NULL column

                    $insImg = "INSERT INTO csm_inventory_category_images (category_id, file_name, file_url, is_primary)
                               VALUES (".(int)$category_id.", '"._esc($newName)."', '"._esc($fileUrl)."', ".(int)$isPrimary.")";
                    call_mysql_query($insImg);

                    $insertedImg++;
                }
            }

            echo "success";
            exit();
        }

        case 'list_category': {
            $sql = "
                SELECT
                    c.*,
                    (
                        SELECT CONCAT('upload/category/', i.file_name)
                        FROM csm_inventory_category_images i
                        WHERE i.category_id = c.category_id
                        ORDER BY (CASE WHEN IFNULL(i.is_primary,0)=1 THEN 0 ELSE 1 END), i.image_id ASC
                        LIMIT 1
                    ) AS primary_image
                FROM csm_inventory_category c
                ORDER BY c.created_at DESC
            ";
            $res = call_mysql_query($sql);
            $items = [];
            if ($res) while ($r = call_mysql_fetch_array($res)) $items[] = $r;
            json_out(['success'=>true,'data'=>$items]);
        }

        case 'get_category': {
            $category_id = _int(_post('category_id'));
            if ($category_id <= 0) json_out(['success'=>false,'message'=>'Invalid category_id.'], 422);

            $res = call_mysql_query("SELECT * FROM csm_inventory_category WHERE category_id={$category_id} LIMIT 1");
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) json_out(['success'=>false,'message'=>'Record not found.'], 404);

            json_out(['success'=>true,'data'=>$row]);
        }

        case 'update_category': {
            header('Content-Type: text/plain; charset=utf-8');

            $category_id = _int(_post('category_id'));
            if ($category_id <= 0) { http_response_code(422); echo "Invalid category_id."; exit(); }

            $item_category_name = _post('item_category_name');
            if ($item_category_name === '') { http_response_code(422); echo "Category Name is required."; exit(); }
            $item_category_name = preg_replace('/\s+/', ' ', trim($item_category_name));

            $chkName = call_mysql_query("
                SELECT category_id
                FROM csm_inventory_category
                WHERE LOWER(TRIM(item_category_name)) = LOWER('"._esc($item_category_name)."')
                  AND category_id <> {$category_id}
                LIMIT 1
            ");
            if ($chkName && call_mysql_fetch_array($chkName)) {
                http_response_code(409);
                echo "Category Name already exists.";
                exit();
            }

            $ok = call_mysql_query("UPDATE csm_inventory_category
                                    SET item_category_name='"._esc($item_category_name)."'
                                    WHERE category_id={$category_id} LIMIT 1");
            if ($ok) { echo "success"; exit(); }

            http_response_code(500);
            echo "Database update failed.";
            exit();
        }

        case 'delete_category': {
            header('Content-Type: text/plain; charset=utf-8');

            $category_id = _int(_post('category_id'));
            if ($category_id <= 0) { http_response_code(422); echo "Invalid category_id."; exit(); }

            $imgRes = call_mysql_query("SELECT image_id, file_name, file_url FROM csm_inventory_category_images WHERE category_id={$category_id}");
            if ($imgRes) {
                while ($img = call_mysql_fetch_array($imgRes)) {
                    $fn = (string)($img['file_name'] ?? '');
                    if ($fn !== '' && file_exists($UPLOAD_ABS_DIR.$fn)) @unlink($UPLOAD_ABS_DIR.$fn);
                }
            }
            call_mysql_query("DELETE FROM csm_inventory_category_images WHERE category_id={$category_id}");

            $ok = call_mysql_query("DELETE FROM csm_inventory_category WHERE category_id={$category_id} LIMIT 1");
            if ($ok) { echo "success"; exit(); }

            http_response_code(500);
            echo "Database delete failed.";
            exit();
        }

        /* ===================== CATEGORY IMAGES ===================== */

        case 'list_category_images': {
            $category_id = _int(_post('category_id'));
            if ($category_id <= 0) json_out(['success'=>false,'message'=>'Invalid category_id.'], 422);

            $res = call_mysql_query("
                SELECT image_id, category_id, file_name, file_url, IFNULL(is_primary,0) AS is_primary
                FROM csm_inventory_category_images
                WHERE category_id={$category_id}
                ORDER BY (CASE WHEN IFNULL(is_primary,0)=1 THEN 0 ELSE 1 END), image_id ASC
            ");

            $imgs = [];
            if ($res) {
                while ($r = call_mysql_fetch_array($res)) {
                    $fu = trim((string)($r['file_url'] ?? ''));
                    if ($fu === '' && !empty($r['file_name'])) {
                        $fu = $UPLOAD_REL_DIR . $r['file_name'];
                    }
                    $r['file_url'] = $fu;
                    $imgs[] = $r;
                }
            }
            json_out(['success'=>true,'data'=>$imgs]);
        }

        case 'upload_category_images': {
            $category_id = _int(_post('category_id'));
            if ($category_id <= 0) json_out(['success'=>false,'message'=>'Invalid category_id.'], 422);

            if (!isset($_FILES['images']) || !is_array($_FILES['images']['name'])) {
                json_out(['success'=>false,'message'=>'No images provided.'], 422);
            }

            $hasPrimary = false;
            $chk = call_mysql_query("SELECT image_id FROM csm_inventory_category_images WHERE category_id={$category_id} AND IFNULL(is_primary,0)=1 LIMIT 1");
            if ($chk && call_mysql_fetch_array($chk)) $hasPrimary = true;

            $names = $_FILES['images']['name'];
            $tmps  = $_FILES['images']['tmp_name'];
            $errs  = $_FILES['images']['error'];

            $inserted = 0;
            for ($i=0; $i<count($names); $i++){
                if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                if (!is_uploaded_file($tmps[$i])) continue;

                $orig = (string)$names[$i];
                $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) continue;

                $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
                $newName = 'cat_' . $category_id . '_' . time() . '_' . $i . '_' . $safeBase . '.' . $ext;

                if (!move_uploaded_file($tmps[$i], $UPLOAD_ABS_DIR . $newName)) continue;

                $isPrimary = (!$hasPrimary && $inserted === 0) ? 1 : 0;
                $fileUrl = $UPLOAD_REL_DIR . $newName;

                $ok = call_mysql_query("
                    INSERT INTO csm_inventory_category_images (category_id, file_name, file_url, is_primary)
                    VALUES ({$category_id}, '"._esc($newName)."', '"._esc($fileUrl)."', ".(int)$isPrimary.")
                ");
                if ($ok) $inserted++;
            }

            json_out(['success'=>true,'inserted'=>$inserted]);
        }

        case 'set_primary_image': {
            $category_id = _int(_post('category_id'));
            $image_id = _int(_post('image_id'));
            if ($category_id <= 0 || $image_id <= 0) json_out(['success'=>false,'message'=>'Invalid ids.'], 422);

            call_mysql_query("UPDATE csm_inventory_category_images SET is_primary=0 WHERE category_id={$category_id}");
            $ok = call_mysql_query("UPDATE csm_inventory_category_images SET is_primary=1 WHERE category_id={$category_id} AND image_id={$image_id} LIMIT 1");

            json_out(['success'=> (bool)$ok]);
        }

        case 'delete_category_image': {
            $category_id = _int(_post('category_id'));
            $image_id = _int(_post('image_id'));
            if ($category_id <= 0 || $image_id <= 0) json_out(['success'=>false,'message'=>'Invalid ids.'], 422);

            $res = call_mysql_query("SELECT file_name, file_url, IFNULL(is_primary,0) AS is_primary
                                     FROM csm_inventory_category_images
                                     WHERE category_id={$category_id} AND image_id={$image_id} LIMIT 1");
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) json_out(['success'=>false,'message'=>'Not found.'], 404);

            $file = (string)($row['file_name'] ?? '');
            $wasPrimary = ((int)($row['is_primary'] ?? 0) === 1);

            $ok = call_mysql_query("DELETE FROM csm_inventory_category_images WHERE category_id={$category_id} AND image_id={$image_id} LIMIT 1");
            if ($ok && $file !== '' && file_exists($UPLOAD_ABS_DIR.$file)) @unlink($UPLOAD_ABS_DIR.$file);

            if ($wasPrimary) {
                $next = call_mysql_query("SELECT image_id FROM csm_inventory_category_images WHERE category_id={$category_id} ORDER BY image_id ASC LIMIT 1");
                if ($next && ($n = call_mysql_fetch_array($next))) {
                    $nid = (int)$n['image_id'];
                    call_mysql_query("UPDATE csm_inventory_category_images SET is_primary=1 WHERE category_id={$category_id} AND image_id={$nid} LIMIT 1");
                }
            }

            json_out(['success'=>true]);
        }

        /* ===================== INVENTORY IMAGE ASSIGNMENT ===================== */

        case 'list_inventory_by_category': {
            $category_id = _int(_post('category_id'));
            if ($category_id <= 0) json_out(['success'=>false,'message'=>'Invalid category_id.'], 422);

            $r = call_mysql_query("SELECT item_category_code FROM csm_inventory_category WHERE category_id={$category_id} LIMIT 1");
            $cat = $r ? call_mysql_fetch_array($r) : null;
            if (!$cat) json_out(['success'=>false,'message'=>'Category not found.'], 404);

            $catCode = normalize_catcode_php($cat['item_category_code'] ?? '');
            if ($catCode === '') json_out(['success'=>false,'message'=>'Category code missing.'], 422);

            $sql = "
                SELECT
                    i.inventory_id,
                    i.inventory_system_item_code AS inventory_code,
                    i.item_description           AS item_name,
                    i.item_category_code,
                    i.item_category_img          AS category_image_id,
                    CASE
                      WHEN i.item_category_img IS NULL OR i.item_category_img = '' THEN NULL
                      WHEN i.item_category_img LIKE 'upload/%' THEN i.item_category_img
                      WHEN i.item_category_img LIKE '%/%' THEN i.item_category_img
                      WHEN i.item_category_img REGEXP '^[0-9]+$' THEN (
                        SELECT ci.file_url
                        FROM csm_inventory_category_images ci
                        WHERE ci.image_id = CAST(i.item_category_img AS UNSIGNED)
                        LIMIT 1
                      )
                      ELSE CONCAT('upload/category/', i.item_category_img)
                    END AS assigned_image_url
                FROM csm_inventory i
                WHERE REPLACE(REPLACE(UPPER(TRIM(i.item_category_code)), ' ', ''), '-', '') =
                      '"._esc($catCode)."'
                ORDER BY i.inventory_id DESC
            ";

            $res = call_mysql_query($sql);
            if ($res === false) {
                json_out(['success'=>false,'message'=>'Query failed: list_inventory_by_category'], 500);
            }

            $items = [];
            while ($row = call_mysql_fetch_array($res)) $items[] = $row;

            json_out(['success'=>true,'data'=>$items]);
        }

        case 'assign_inventory_image': {
            $inventory_id = _int(_post('inventory_id'));
            if ($inventory_id <= 0) json_out(['success'=>false,'message'=>'Invalid inventory_id.'], 422);

            $rawImageId = _post('image_id', '');
            $image_id = ($rawImageId === '') ? 0 : (int)$rawImageId;

            if ($image_id <= 0) {
                $ok = call_mysql_query("UPDATE csm_inventory SET item_category_img=NULL WHERE inventory_id={$inventory_id} LIMIT 1");
                json_out(['success'=>(bool)$ok]);
            }

            $imgRes = call_mysql_query("SELECT image_id FROM csm_inventory_category_images WHERE image_id={$image_id} LIMIT 1");
            $img = $imgRes ? call_mysql_fetch_array($imgRes) : null;
            if (!$img) json_out(['success'=>false,'message'=>'Image not found.'], 404);

            $ok = call_mysql_query("UPDATE csm_inventory SET item_category_img='"._esc((string)$image_id)."' WHERE inventory_id={$inventory_id} LIMIT 1");
            json_out(['success'=>(bool)$ok]);
        }

        default: {
            http_response_code(400);
            echo "Unknown action.";
            exit();
        }
    }

} catch (Throwable $e) {
    release_code_lock();
    json_out(['success'=>false,'message'=>'Server error: '.$e->getMessage()], 500);
}
