<?php
// admin/modules/nonconsumable/process/ast_category_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

// Access control
$isStaffAst = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("AST", "PO")));
if (!isset($g_user_role) || (!role_has("ADMIN") && !$isStaffAst)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit();
}

// Helper functions
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

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

// Upload directory settings
$UPLOAD_REL_DIR = 'upload/category/';
$UPLOAD_ABS_DIR = dirname(__DIR__, 4) . '/' . $UPLOAD_REL_DIR;

if (!is_dir($UPLOAD_ABS_DIR)) {
    @mkdir($UPLOAD_ABS_DIR, 0777, true);
}

$action = _post('action', '');
if ($action === '') {
    json_response(['success' => false, 'message' => 'Missing action.'], 400);
}

try {
    switch ($action) {
        
        // LIST ALL CATEGORIES
        case 'list_categories':
            $sql = "SELECT 
                        category_id,
                        item_category_name,
                        category_photo,
                        created_at,
                        updated_at
                    FROM ast_inventory_category
                    ORDER BY created_at DESC";
            
            $res = call_mysql_query($sql);
            $categories = [];
            
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['category_photo_url'] = $row['category_photo'] 
                        ? BASE_URL . $UPLOAD_REL_DIR . $row['category_photo']
                        : null;
                    $row['category_photo_thumb_url'] = $row['category_photo']
                        ? BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($row['category_photo']) . '&s=100'
                        : null;
                    $categories[] = $row;
                }
            }
            
            json_response(['success' => true, 'data' => $categories]);
            break;

        // ADD CATEGORY
        case 'add_category':
            $item_category_name = _post('item_category_name');
            
            if (empty($item_category_name)) {
                json_response(['success' => false, 'message' => 'Category name is required.'], 422);
            }

            // Handle image upload
            $photoName = null;
            if (isset($_FILES['category_photo']) && $_FILES['category_photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['category_photo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $photoName = 'cat_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    
                    if (!move_uploaded_file($file['tmp_name'], $UPLOAD_ABS_DIR . $photoName)) {
                        $photoName = null;
                    }
                }
            }

            // Insert category
            $sql = "INSERT INTO ast_inventory_category 
                    (item_category_name, category_photo)
                    VALUES ('" . _esc($item_category_name) . "', 
                            " . ($photoName ? "'" . _esc($photoName) . "'" : "NULL") . ")";
            
            $ok = call_mysql_query($sql);
            
            if (!$ok) {
                // Clean up uploaded file if DB insert failed
                if ($photoName && file_exists($UPLOAD_ABS_DIR . $photoName)) {
                    @unlink($UPLOAD_ABS_DIR . $photoName);
                }
                json_response(['success' => false, 'message' => 'Failed to add category.'], 500);
            }

            activity_log_new("AST CATEGORY ADD", "SUCCESS", array(
                'category_name' => $item_category_name,
                'category_photo' => $photoName
            ));
            json_response(['success' => true, 'message' => 'Category added successfully.']);
            break;

        // GET SINGLE CATEGORY
        case 'get_category':
            $category_id = _int(_post('category_id'));
            
            if ($category_id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid category ID.'], 422);
            }

            $sql = "SELECT * FROM ast_inventory_category WHERE category_id = {$category_id} LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            
            if (!$row) {
                json_response(['success' => false, 'message' => 'Category not found.'], 404);
            }

            $row['category_photo_url'] = $row['category_photo'] 
                ? BASE_URL . $UPLOAD_REL_DIR . $row['category_photo']
                : null;
            $row['category_photo_thumb_url'] = $row['category_photo']
                ? BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($row['category_photo']) . '&s=100'
                : null;

            json_response(['success' => true, 'data' => $row]);
            break;

        // UPDATE CATEGORY
        case 'update_category':
            $category_id = _int(_post('category_id'));
            $item_category_name = _post('item_category_name');
            
            if ($category_id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid category ID.'], 422);
            }
            
            if (empty($item_category_name)) {
                json_response(['success' => false, 'message' => 'Category name is required.'], 422);
            }

            // Get current photo + name for audit log
            $sqlGet = "SELECT item_category_name, category_photo FROM ast_inventory_category WHERE category_id = {$category_id} LIMIT 1";
            $resGet = call_mysql_query($sqlGet);
            $currentRow = $resGet ? call_mysql_fetch_array($resGet) : null;
            $currentPhoto = $currentRow ? $currentRow['category_photo'] : null;
            $currentName = $currentRow ? ($currentRow['item_category_name'] ?? '') : '';

            // Handle new image upload
            $photoName = $currentPhoto;
            if (isset($_FILES['category_photo']) && $_FILES['category_photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['category_photo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $photoName = 'cat_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    
                    if (move_uploaded_file($file['tmp_name'], $UPLOAD_ABS_DIR . $photoName)) {
                        // Delete old photo if exists
                        if ($currentPhoto && file_exists($UPLOAD_ABS_DIR . $currentPhoto)) {
                            @unlink($UPLOAD_ABS_DIR . $currentPhoto);
                        }
                    } else {
                        $photoName = $currentPhoto; // Keep old photo if upload fails
                    }
                }
            }

            // Update category
            $sql = "UPDATE ast_inventory_category 
                    SET item_category_name = '" . _esc($item_category_name) . "',
                        category_photo = " . ($photoName ? "'" . _esc($photoName) . "'" : "NULL") . "
                    WHERE category_id = {$category_id} LIMIT 1";
            
            $ok = call_mysql_query($sql);
            
            if (!$ok) {
                json_response(['success' => false, 'message' => 'Failed to update category.'], 500);
            }

            activity_log_new("AST CATEGORY UPDATE", "SUCCESS", array(
                'category_id' => $category_id,
                'old_name' => $currentName,
                'new_name' => $item_category_name,
                'old_photo' => $currentPhoto,
                'new_photo' => $photoName
            ));
            json_response(['success' => true, 'message' => 'Category updated successfully.']);
            break;

        // DELETE CATEGORY
        case 'delete_category':
            $category_id = _int(_post('category_id'));
            
            if ($category_id <= 0) {
                json_response(['success' => false, 'message' => 'Invalid category ID.'], 422);
            }

            // Get current data for audit log
            $sqlGet = "SELECT item_category_name, category_photo FROM ast_inventory_category WHERE category_id = {$category_id} LIMIT 1";
            $resGet = call_mysql_query($sqlGet);
            $row = $resGet ? call_mysql_fetch_array($resGet) : null;
            $photoName = $row ? $row['category_photo'] : null;
            $categoryName = $row ? ($row['item_category_name'] ?? '') : '';

            // Delete from database
            $sql = "DELETE FROM ast_inventory_category WHERE category_id = {$category_id} LIMIT 1";
            $ok = call_mysql_query($sql);
            
            if (!$ok) {
                json_response(['success' => false, 'message' => 'Failed to delete category.'], 500);
            }

            // Delete photo file if exists
            if ($photoName && file_exists($UPLOAD_ABS_DIR . $photoName)) {
                @unlink($UPLOAD_ABS_DIR . $photoName);
            }

            activity_log_new("AST CATEGORY DELETE", "SUCCESS", array(
                'category_id' => $category_id,
                'category_name' => $categoryName,
                'category_photo' => $photoName
            ));
            json_response(['success' => true, 'message' => 'Category deleted successfully.']);
            break;

        // BULK ADD FROM WEB FORM
        case 'bulk_add_categories':
            $names = isset($_POST['bulk_names']) && is_array($_POST['bulk_names']) ? $_POST['bulk_names'] : [];
            if (empty($names)) {
                json_response(['success' => false, 'message' => 'Please add at least one category.'], 422);
            }

            $inserted = 0;
            $skipped = 0;
            $errors = [];
            $rowIndex = 0;

            // Pull existing names for fast duplicate checks
            $existingNames = [];
            $resExisting = call_mysql_query("SELECT item_category_name FROM ast_inventory_category");
            if ($resExisting) {
                while ($r = call_mysql_fetch_array($resExisting)) {
                    $nameKey = strtolower(trim((string)$r['item_category_name']));
                    if ($nameKey !== '') $existingNames[$nameKey] = true;
                }
            }

            foreach ($names as $i => $rawName) {
                $rowIndex++;
                $name = trim((string)$rawName);

                if (empty($name)) {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Category name is required.";
                    continue;
                }

                $nameKey = strtolower($name);
                if (isset($existingNames[$nameKey])) {
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Category name already exists.";
                    continue;
                }

                // Optional photo upload per row
                $photoName = null;
                $fileKey = 'bulk_photo_' . $i;
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$fileKey];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                        $photoName = 'cat_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                        if (!move_uploaded_file($file['tmp_name'], $UPLOAD_ABS_DIR . $photoName)) {
                            $photoName = null;
                        }
                    }
                }

                // Insert
                $insSql = "INSERT INTO ast_inventory_category (item_category_name, category_photo)
                          VALUES ('" . _esc($name) . "', " . ($photoName ? "'" . _esc($photoName) . "'" : "NULL") . ")";
                
                if (call_mysql_query($insSql)) {
                    $inserted++;
                    $existingNames[$nameKey] = true;
                } else {
                    if ($photoName && file_exists($UPLOAD_ABS_DIR . $photoName)) {
                        @unlink($UPLOAD_ABS_DIR . $photoName);
                    }
                    $skipped++;
                    $errors[] = "Row {$rowIndex}: Failed to insert.";
                }
            }

            activity_log_new("AST CATEGORY BULK ADD", "SUCCESS", array(
                'inserted' => $inserted,
                'skipped' => $skipped,
                'error_count' => count($errors)
            ));

            json_response([
                'success' => true,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'errors' => $errors
            ]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
            break;
    }

} catch (Throwable $e) {
    error_log($e->getMessage());
    json_response(['success' => false, 'message' => 'Server error occurred.'], 500);
}
