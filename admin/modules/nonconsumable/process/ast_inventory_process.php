<?php
// admin/modules/nonconsumable/process/ast_inventory_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require_once dirname(__DIR__, 4) . '/call_func/phpqrcode/qrlib.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

$isStaffAst = ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access(array("AST", "PO")));
if (!isset($g_user_role) || (!role_has("ADMIN") && !$isStaffAst)) {
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

function _float_or_null($v) {
    if ($v === '' || $v === null) return null;
    return round((float)$v, 2);
}

function _json_array($v) {
    if ($v === null || $v === '') return [];
    if (is_array($v)) return $v;
    $decoded = json_decode((string)$v, true);
    return is_array($decoded) ? $decoded : [];
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

    // Structured format
    if (array_key_exists('teaching', $decoded) || array_key_exists('non_teaching', $decoded) || array_key_exists('none', $decoded)) {
        $teaching = array_values(array_filter(array_map('intval', (array)($decoded['teaching'] ?? []))));
        $non = array_values(array_filter(array_map('intval', (array)($decoded['non_teaching'] ?? []))));
        $none = !empty($decoded['none']);
        if ($none) {
            return [
                'mode' => 'none',
                'teaching' => [],
                'non_teaching' => []
            ];
        }
        if (empty($teaching) && empty($non)) {
            return [
                'mode' => 'none',
                'teaching' => [],
                'non_teaching' => []
            ];
        }
        return [
            'mode' => 'structured',
            'teaching' => $teaching,
            'non_teaching' => $non
        ];
    }

    // Legacy list format
    $list = array_values($decoded);
    $listStr = array_map('strval', $list);
    if (in_array('NONE', $listStr, true)) {
        return [
            'mode' => 'none',
            'teaching' => [],
            'non_teaching' => []
        ];
    }
    $ids = array_values(array_filter(array_map('intval', $list)));
    if (empty($ids)) {
        return [
            'mode' => 'all',
            'teaching' => [],
            'non_teaching' => []
        ];
    }
    return [
        'mode' => 'legacy',
        'teaching' => $ids,
        'non_teaching' => $ids
    ];
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
        $labels[] = 'Teaching: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Teaching: None';
    }

    if (!empty($non)) {
        $names = [];
        foreach ($non as $sid) {
            $sid = (int)$sid;
            if (isset($statusMap[$sid])) $names[] = $statusMap[$sid];
        }
        $labels[] = 'Non-Teaching: ' . (!empty($names) ? implode(', ', $names) : 'None');
    } else {
        $labels[] = 'Non-Teaching: None';
    }

    return implode(' | ', $labels);
}

function normalize_allowed_payload($raw) {
    if ($raw === null || $raw === '') {
        return [
            'mode' => 'all',
            'json' => null,
            'teaching' => [],
            'non_teaching' => []
        ];
    }
    $decoded = is_array($raw) ? $raw : json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        return [
            'mode' => 'all',
            'json' => null,
            'teaching' => [],
            'non_teaching' => []
        ];
    }

    if (array_key_exists('teaching', $decoded) || array_key_exists('non_teaching', $decoded) || array_key_exists('none', $decoded)) {
        $none = !empty($decoded['none']);
        if ($none) {
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
            return [
                'mode' => 'none',
                'json' => json_encode(['none' => true]),
                'teaching' => [],
                'non_teaching' => []
            ];
        }
        return [
            'mode' => 'structured',
            'json' => json_encode(['teaching' => $teaching, 'non_teaching' => $non]),
            'teaching' => $teaching,
            'non_teaching' => $non
        ];
    }

    // Legacy list
    $list = array_values($decoded);
    $listStr = array_map('strval', $list);
    if (in_array('NONE', $listStr, true)) {
        return [
            'mode' => 'none',
            'json' => json_encode(['none' => true]),
            'teaching' => [],
            'non_teaching' => []
        ];
    }
    $ids = array_values(array_filter(array_map('intval', $list)));
    if (empty($ids)) {
        return [
            'mode' => 'all',
            'json' => null,
            'teaching' => [],
            'non_teaching' => []
        ];
    }
    return [
        'mode' => 'legacy',
        'json' => json_encode(['teaching' => $ids, 'non_teaching' => $ids]),
        'teaching' => $ids,
        'non_teaching' => $ids
    ];
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

function sanitize_property_number($v) {
    return strtoupper(trim((string)$v));
}

function is_valid_property_number($v) {
    return (bool)preg_match('/^[A-Z0-9]+$/', (string)$v);
}

function sanitize_property_code($v) {
    $v = strtoupper(trim((string)$v));
    // Keep alphanumeric and dashes only
    return preg_replace('/[^A-Z0-9-]/', '', $v);
}

function sanitize_serial_number($v) {
    return trim((string)$v);
}

function has_serial_number_column($refresh = false) {
    static $has = null;
    if ($refresh) {
        $has = null;
    }
    if ($has !== null) return $has;
    $res = call_mysql_query("SHOW COLUMNS FROM ast_inventory LIKE 'serial_number'");
    $has = ($res && mysqli_num_rows($res) > 0);
    return $has;
}

function ensure_serial_number_column() {
    $has = has_serial_number_column();
    if ($has) return true;
    call_mysql_query("ALTER TABLE ast_inventory ADD COLUMN serial_number varchar(150) DEFAULT NULL AFTER item_description");
    return has_serial_number_column(true);
}

function build_property_code($propertyNumber, $series) {
    $propertyNumber = sanitize_property_number($propertyNumber);
    $seriesPadded = str_pad((string)$series, 4, '0', STR_PAD_LEFT);
    return 'AST-' . $propertyNumber . '-' . $seriesPadded;
}

function get_next_series($propertyNumber) {
    $propertyNumber = sanitize_property_number($propertyNumber);
    $sql = "SELECT MAX(property_series) AS max_series FROM ast_inventory WHERE property_number = '" . _esc($propertyNumber) . "'";
    $res = call_mysql_query($sql);
    $max = 0;
    if ($res && ($row = call_mysql_fetch_array($res))) {
        $max = (int)($row['max_series'] ?? 0);
    }
    return $max + 1;
}

function upload_image($fieldName, $prefix, $uploadDirAbs, $allowedExt = ['jpg','jpeg','png','webp','gif']) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$fieldName];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return null;
    }
    $fileName = $prefix . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDirAbs . $fileName)) {
        return null;
    }
    return $fileName;
}

function generate_qr_image($value, $prefix, $uploadDirAbs) {
    if ($value === '') {
        return null;
    }
    $fileName = $prefix . date('Ymd_His') . '_' . uniqid() . '.png';
    $filePath = $uploadDirAbs . $fileName;

    $canRenderLocal = class_exists('QRcode') && extension_loaded('gd') && function_exists('imagepng');
    if ($canRenderLocal) {
        QRcode::png($value, $filePath, QR_ECLEVEL_L, 4);
        return file_exists($filePath) ? $fileName : null;
    }

    // Fallback for machines without GD enabled.
    $fallbackUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&format=png&data=' . rawurlencode($value);
    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 8,
            'ignore_errors' => true
        )
    ));
    $png = @file_get_contents($fallbackUrl, false, $ctx);
    if ($png !== false && strlen($png) > 0) {
        $written = @file_put_contents($filePath, $png);
        if ($written !== false && file_exists($filePath)) {
            return $fileName;
        }
    }

    return null;
}

$UPLOAD_REL_DIR = 'upload/ast_inventory/';
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
        case 'check_serial_exists':
            $serial = sanitize_serial_number(_post('serial_number'));
            if ($serial === '') {
                json_response(['success' => true, 'exists' => false]);
            }
            if (!has_serial_number_column()) {
                json_response(['success' => true, 'exists' => false]);
            }
            $serialEsc = _esc($serial);
            $res = call_mysql_query("SELECT serial_number FROM ast_inventory WHERE serial_number = '{$serialEsc}' LIMIT 1");
            $exists = ($res && call_mysql_fetch_array($res)) ? true : false;
            json_response(['success' => true, 'exists' => $exists]);
            break;

        case 'list_categories':
            $sql = "SELECT category_id, item_category_name, category_photo FROM ast_inventory_category ORDER BY item_category_name";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $row['category_photo_url'] = $row['category_photo'] ? BASE_URL . 'upload/category/' . $row['category_photo'] : null;
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'list_units':
            $sql = "SELECT DISTINCT TRIM(unit) AS unit
                    FROM ast_inventory
                    WHERE unit IS NOT NULL AND TRIM(unit) <> ''
                    ORDER BY unit ASC";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $unit = trim((string)($row['unit'] ?? ''));
                    if ($unit !== '') {
                        $rows[] = $unit;
                    }
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'list_property_codes':
            $limit = _int(_post('limit', 500));
            if ($limit <= 0 || $limit > 2000) $limit = 500;

            $sql = "SELECT i.property_code, i.item_description, i.quantity, i.unit, c.item_category_name
                    FROM ast_inventory i
                    LEFT JOIN ast_inventory_category c ON c.category_id = i.category_id
                    ORDER BY i.property_code ASC 
                    LIMIT {$limit}";
            $res = call_mysql_query($sql);
            $rows = [];
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $rows[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'get_next_property_code':
            $property_number = sanitize_property_number(_post('property_number'));
            $number_of_units = _int(_post('number_of_units'), 1);
            if ($number_of_units <= 0) $number_of_units = 1;
            if ($number_of_units > 500) $number_of_units = 500;
            if ($property_number === '') {
                json_response(['success' => false, 'message' => 'Property number is required.'], 422);
            }
            if (!is_valid_property_number($property_number)) {
                json_response(['success' => false, 'message' => 'Property number must contain letters and numbers only.'], 422);
            }
            $nextSeries = get_next_series($property_number);
            $property_code = build_property_code($property_number, $nextSeries);
            $endSeries = $nextSeries + ($number_of_units - 1);
            $range_end_code = build_property_code($property_number, $endSeries);
            json_response([
                'success' => true,
                'property_number' => $property_number,
                'series' => $nextSeries,
                'series_padded' => str_pad((string)$nextSeries, 4, '0', STR_PAD_LEFT),
                'property_code' => $property_code,
                'range_end_code' => $range_end_code,
                'number_of_units' => $number_of_units
            ]);
            break;

        case 'list_items':
            $limit = _int(_post('limit', 100));
            if ($limit <= 0 || $limit > 500) $limit = 100;
            $search = _post('search');
            $hasSerial = has_serial_number_column();

            $where = '';
            if ($search !== '') {
                $searchEsc = _esc('%' . $search . '%');
                $parts = [
                    "i.property_code LIKE '{$searchEsc}'",
                    "i.property_number LIKE '{$searchEsc}'",
                    "i.item_description LIKE '{$searchEsc}'",
                    "c.item_category_name LIKE '{$searchEsc}'"
                ];
                if ($hasSerial) {
                    $parts[] = "i.serial_number LIKE '{$searchEsc}'";
                }
                $where = "WHERE (" . implode(' OR ', $parts) . ")";
            }

                $sql = "SELECT 
                    i.*, 
                    c.item_category_name, 
                    c.category_photo,
                    (SELECT CONCAT(COALESCE(u.f_name,''), ' ', COALESCE(u.l_name,''))
                     FROM facility_records_assignments a
                     LEFT JOIN users u ON u.user_id = a.issued_to_user_id
                     WHERE a.module_type = 'AST' AND a.item_code = i.property_code
                     ORDER BY a.issued_at DESC, a.assignment_id DESC
                     LIMIT 1) AS issued_to_name
                    FROM ast_inventory i
                    LEFT JOIN ast_inventory_category c ON c.category_id = i.category_id
                    {$where}
                    ORDER BY i.created_at DESC
                    LIMIT {$limit}";

            $res = call_mysql_query($sql);
            $items = [];
            $statusMap = get_employment_status_map();
            if ($res) {
                while ($row = call_mysql_fetch_array($res)) {
                    $norm = normalize_allowed_employment($row['allowed_employment_status'] ?? '');
                    $row['allowed_status_names'] = build_allowed_status_label($norm, $statusMap);
                    if (!isset($row['serial_number'])) {
                        $row['serial_number'] = '';
                    }
                    $row['category_photo_url'] = $row['category_photo']
                        ? BASE_URL . 'upload/category/' . $row['category_photo']
                        : null;
                    $row['category_photo_thumb_url'] = $row['category_photo']
                        ? BASE_URL . 'admin/modules/tools/category_image_thumb.php?f=' . urlencode($row['category_photo']) . '&s=100'
                        : null;
                    // issued_to_name already included from subquery
                    if (!empty($row['qr_image'])) {
                        $row['qr_image_url'] = BASE_URL . $UPLOAD_REL_DIR . $row['qr_image'];
                    } elseif (!empty($row['property_code'])) {
                        $row['qr_image_url'] = BASE_URL . 'admin/modules/tools/qr_image.php?v=' . urlencode($row['property_code']);
                    } else {
                        $row['qr_image_url'] = null;
                    }
                    $items[] = $row;
                }
            }
            json_response(['success' => true, 'data' => $items]);
            break;

        case 'list_employment_status':
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
            json_response(['success' => true, 'data' => $rows]);
            break;

        case 'get_availability_settings':
            $property_code = _post('property_code');
            if ($property_code === '') {
                json_response(['success' => false, 'message' => 'Property code is required.'], 422);
            }
            $sql = "SELECT property_code, quantity, allowed_employment_status
                    FROM ast_inventory
                    WHERE property_code = '" . _esc($property_code) . "' LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }
            $norm = normalize_allowed_employment($row['allowed_employment_status'] ?? '');
            $row['allowed_employment_status'] = [
                'teaching' => $norm['teaching'] ?? [],
                'non_teaching' => $norm['non_teaching'] ?? [],
                'mode' => $norm['mode'] ?? 'all'
            ];
            json_response(['success' => true, 'data' => $row]);
            break;

        case 'update_availability_settings':
            $property_code = _post('property_code');
            $allowed_status = _post('allowed_status', '');
            $allowed_norm = normalize_allowed_payload($allowed_status);
            if ($property_code === '') {
                json_response(['success' => false, 'message' => 'Property code is required.'], 422);
            }
            $sql = "SELECT item_id, property_code, allowed_employment_status, is_available FROM ast_inventory WHERE property_code = '" . _esc($property_code) . "' LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }
            $prev_allowed = $row['allowed_employment_status'] ?? null;
            $prev_is_available = (int)($row['is_available'] ?? 0);
            $allowed_json = $allowed_norm['json'];
            $is_available = ($allowed_norm['mode'] !== 'none') ? 1 : 0;
            $updateSql = "UPDATE ast_inventory SET 
                            allowed_employment_status = " . ($allowed_json ? "'" . _esc($allowed_json) . "'" : "NULL") . ",
                            is_available = {$is_available},
                            updated_at = NOW()
                          WHERE item_id = " . (int)$row['item_id'] . " LIMIT 1";
            $ok = call_mysql_query($updateSql);
            if (!$ok) {
                json_response(['success' => false, 'message' => 'Failed to update availability settings.'], 500);
            }
            activity_log_new("AST SET AVAILABLE RULES", "SUCCESS", array(
                'property_code' => $property_code,
                'old_allowed_status' => $prev_allowed,
                'new_allowed_status' => $allowed_json,
                'old_is_available' => $prev_is_available,
                'new_is_available' => $is_available
            ));
            json_response(['success' => true, 'message' => 'Availability settings updated.']);
            break;

        case 'update_availability_settings_bulk':
            $bulk_codes = _post('bulk_codes');
            $allowed_status = _post('allowed_status', '');
            $allowed_norm = normalize_allowed_payload($allowed_status);
            if ($bulk_codes === '') {
                json_response(['success' => false, 'message' => 'No items selected.'], 422);
            }
            $codes = array_filter(array_map('trim', explode(',', $bulk_codes)));
            if (empty($codes)) {
                json_response(['success' => false, 'message' => 'No items selected.'], 422);
            }
            $codesEsc = array_map(function($c){ return "'" . _esc($c) . "'"; }, $codes);
            $allowed_json = $allowed_norm['json'];
            $is_available = ($allowed_norm['mode'] !== 'none') ? 1 : 0;
            $set = [];
            $set[] = "allowed_employment_status = " . ($allowed_json ? "'" . _esc($allowed_json) . "'" : "NULL");
            $set[] = "is_available = {$is_available}";
            $set[] = "updated_at = NOW()";
            $updateSql = "UPDATE ast_inventory SET " . implode(', ', $set) . "
                          WHERE property_code IN (" . implode(',', $codesEsc) . ")";
            $ok = call_mysql_query($updateSql);
            if (!$ok) {
                json_response(['success' => false, 'message' => 'Failed to update availability settings.'], 500);
            }
            activity_log_new("AST BULK SET AVAILABLE RULES", "SUCCESS", array(
                'count' => count($codes),
                'property_codes' => $codes,
                'allowed_status' => $allowed_json
            ));
            json_response(['success' => true, 'message' => 'Availability settings updated for selected items.']);
            break;

        case 'add_item':
            $property_number = sanitize_property_number(_post('property_number'));
            $item_description = _post('item_description');
            $number_of_units = _int(_post('number_of_units'), 1);
            $serial_numbers_json = _post('serial_numbers_json');
            $serial_numbers_text = _post('serial_numbers');
            $unit = _post('unit');
            $source_of_fund = _post('source_of_fund');
            $cost_value = _float_or_null(_post('cost_value'));
            $property_series = _int(_post('property_series'));
            $allowed_status = _post('allowed_status', '');
            if ($allowed_status === '') {
                $allowed_status = json_encode(['none' => true]);
            }
            $allowed_norm = normalize_allowed_payload($allowed_status);

            $category_id = _int(_post('category_id'));
            $serial_numbers = [];
            if ($serial_numbers_json !== '') {
                $decoded = json_decode($serial_numbers_json, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $line) {
                        $serial_numbers[] = sanitize_serial_number($line);
                    }
                }
            } elseif ($serial_numbers_text !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $serial_numbers_text);
                foreach ((array)$lines as $line) {
                    $sn = sanitize_serial_number($line);
                    if ($sn !== '') $serial_numbers[] = $sn;
                }
            }
            if (!empty($serial_numbers_json) && count($serial_numbers) > 0) {
                $number_of_units = count($serial_numbers);
            }
            $non_empty_serials = array_values(array_filter($serial_numbers, function($sn) {
                return $sn !== '';
            }));
            $hasSerial = (!empty($non_empty_serials)) ? ensure_serial_number_column() : has_serial_number_column();

            if ($property_number === '') {
                json_response(['success' => false, 'message' => 'Property number is required.'], 422);
            }
            if (!is_valid_property_number($property_number)) {
                json_response(['success' => false, 'message' => 'Property number must contain letters and numbers only.'], 422);
            }
            if ($item_description === '') {
                json_response(['success' => false, 'message' => 'Item description is required.'], 422);
            }
            if ($number_of_units <= 0) {
                json_response(['success' => false, 'message' => 'Number of units must be greater than zero.'], 422);
            }
            if ($number_of_units > 200) {
                json_response(['success' => false, 'message' => 'Maximum 200 units per add is allowed.'], 422);
            }
            if ($unit === '') {
                json_response(['success' => false, 'message' => 'Unit is required.'], 422);
            }
            if ($category_id <= 0) {
                json_response(['success' => false, 'message' => 'Category is required.'], 422);
            }
            if (count($serial_numbers) > $number_of_units) {
                json_response(['success' => false, 'message' => 'Serial numbers cannot exceed number of units.'], 422);
            }

            if (!empty($non_empty_serials)) {
                $seen = [];
                foreach ($non_empty_serials as $sn) {
                    if (strlen($sn) > 150) {
                        json_response(['success' => false, 'message' => 'Each serial number must be 150 characters or less.'], 422);
                    }
                    $key = strtolower($sn);
                    if (isset($seen[$key])) {
                        json_response(['success' => false, 'message' => 'Duplicate serial numbers are not allowed in one batch.'], 422);
                    }
                    $seen[$key] = true;
                }
                if ($hasSerial) {
                    $serialEsc = array_map(function($sn){ return "'" . _esc($sn) . "'"; }, $non_empty_serials);
                    if (!empty($serialEsc)) {
                        $dupSql = "SELECT serial_number FROM ast_inventory WHERE serial_number IN (" . implode(',', $serialEsc) . ") LIMIT 1";
                        $dupRes = call_mysql_query($dupSql);
                        if ($dupRes && call_mysql_fetch_array($dupRes)) {
                            json_response(['success' => false, 'message' => 'One or more serial numbers already exist.'], 409);
                        }
                    }
                }
            }

            if ($property_series <= 0) {
                $property_series = get_next_series($property_number);
            }

            $allowed_json = $allowed_norm['json'];
            $is_available = ($allowed_norm['mode'] !== 'none') ? 1 : 0;
            $created_codes = [];
            $created_qr_files = [];

            call_mysql_query("START TRANSACTION");
            $failed = false;
            $failMessage = 'Failed to add item(s).';
            $failCode = 500;

            for ($i = 0; $i < $number_of_units; $i++) {
                $series = $property_series + $i;
                $property_code = build_property_code($property_number, $series);

                $existsSql = "SELECT item_id FROM ast_inventory WHERE property_code = '" . _esc($property_code) . "' LIMIT 1";
                $existsRes = call_mysql_query($existsSql);
                if ($existsRes && call_mysql_fetch_array($existsRes)) {
                    $failed = true;
                    $failMessage = 'Property code already exists. Please refresh and try again.';
                    $failCode = 409;
                    break;
                }

                $qrImage = generate_qr_image($property_code, 'ast_qr_', $UPLOAD_ABS_DIR);
                if (!$qrImage) {
                    $failed = true;
                    $failMessage = 'Failed to generate QR image.';
                    break;
                }
                $created_qr_files[] = $qrImage;

                $serial_for_unit = isset($serial_numbers[$i]) ? $serial_numbers[$i] : '';

                $insertCols = [
                    'property_number',
                    'property_series',
                    'property_code',
                    'category_id',
                    'item_description'
                ];
                $insertVals = [
                    "'" . _esc($property_number) . "'",
                    (int)$series,
                    "'" . _esc($property_code) . "'",
                    (int)$category_id,
                    "'" . _esc($item_description) . "'"
                ];
                if ($hasSerial) {
                    $insertCols[] = 'serial_number';
                    $insertVals[] = ($serial_for_unit !== '' ? "'" . _esc($serial_for_unit) . "'" : "NULL");
                }
                $insertCols = array_merge($insertCols, [
                    'quantity',
                    'allowed_employment_status',
                    'unit',
                    'source_of_fund',
                    'cost_value',
                    'qr_image',
                    'is_available'
                ]);
                $insertVals = array_merge($insertVals, [
                    1,
                    ($allowed_json ? "'" . _esc($allowed_json) . "'" : "NULL"),
                    "'" . _esc($unit) . "'",
                    ($source_of_fund !== '' ? "'" . _esc($source_of_fund) . "'" : "NULL"),
                    ($cost_value !== null ? "'" . _esc($cost_value) . "'" : "NULL"),
                    "'" . _esc($qrImage) . "'",
                    (int)$is_available
                ]);
                $sql = "INSERT INTO ast_inventory (" . implode(', ', $insertCols) . ")
                        VALUES (" . implode(', ', $insertVals) . ")";
                $ok = call_mysql_query($sql);
                if (!$ok) {
                    $failed = true;
                    $failMessage = 'Failed to save one or more units.';
                    break;
                }
                $created_codes[] = $property_code;
            }

            if ($failed) {
                call_mysql_query("ROLLBACK");
                foreach ($created_qr_files as $f) {
                    if ($f && file_exists($UPLOAD_ABS_DIR . $f)) @unlink($UPLOAD_ABS_DIR . $f);
                }
                json_response(['success' => false, 'message' => $failMessage], $failCode);
            }

            call_mysql_query("COMMIT");

            $first_code = isset($created_codes[0]) ? $created_codes[0] : '';
            $last_code = !empty($created_codes) ? $created_codes[count($created_codes) - 1] : '';
            activity_log_new("AST ADD ITEM", "SUCCESS", array(
                'property_code_start' => $first_code,
                'property_code_end' => $last_code,
                'property_number' => $property_number,
                'property_series_start' => $property_series,
                'units_created' => $number_of_units,
                'category_id' => $category_id,
                'item_description' => $item_description,
                'serial_numbers_provided' => count($non_empty_serials),
                'quantity_per_unit' => 1,
                'unit' => $unit,
                'source_of_fund' => $source_of_fund,
                'cost_value' => $cost_value,
                'allowed_status' => $allowed_json,
                'is_available' => $is_available
            ));
            json_response([
                'success' => true,
                'message' => $number_of_units . ' unit(s) added successfully.',
                'property_code' => $first_code,
                'property_code_end' => $last_code,
                'property_codes' => $created_codes
            ]);
            break;

        case 'bulk_upsert':
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                json_response(['success' => false, 'message' => 'CSV file is required.'], 422);
            }
            $hasSerial = ensure_serial_number_column();

            $property_number_base = sanitize_property_number(_post('property_number_base'));
            if ($property_number_base !== '' && !is_valid_property_number($property_number_base)) {
                json_response(['success' => false, 'message' => 'Property number base must contain letters and numbers only.'], 422);
            }

            $fileTmp = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($fileTmp, 'r');
            if (!$handle) {
                json_response(['success' => false, 'message' => 'Unable to read CSV file.'], 500);
            }

            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                json_response(['success' => false, 'message' => 'CSV file is empty.'], 422);
            }

            $map = [];
            foreach ($headers as $idx => $h) {
                $key = strtolower(trim($h));
                $map[$key] = $idx;
            }

            $getCol = function($row, $name) use ($map) {
                $nameLower = strtolower($name);
                if (!isset($map[$nameLower])) return '';
                return isset($row[$map[$nameLower]]) ? trim((string)$row[$map[$nameLower]]) : '';
            };

            $inserted = 0; $updated = 0; $skipped = 0; $errors = [];
            $seriesCache = [];
            $rowNo = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNo++;
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue; // skip empty row
                }

                $property_number = sanitize_property_number($getCol($row, 'property_number'));
                if ($property_number === '' && $property_number_base !== '') {
                    $property_number = $property_number_base;
                }
                if ($property_number !== '' && !is_valid_property_number($property_number)) {
                    $errors[] = 'Invalid property number (letters and numbers only) in row ' . ($inserted + $updated + $skipped + 2);
                    $skipped++;
                    continue;
                }

                $property_code_csv = sanitize_property_code($getCol($row, 'property_code'));
                $property_series_csv = _int($getCol($row, 'property_series'));

                if ($property_number === '' && $property_code_csv === '') {
                    $errors[] = 'Missing property number in row ' . ($inserted + $updated + $skipped + 2);
                    $skipped++;
                    continue;
                }

                $category_name = $getCol($row, 'item_category_name');
                if ($category_name === '') {
                    $errors[] = 'Missing category name in row ' . ($inserted + $updated + $skipped + 2);
                    $skipped++;
                    continue;
                }
                $sqlCat = "SELECT category_id FROM ast_inventory_category WHERE item_category_name = '" . _esc($category_name) . "' LIMIT 1";
                $resCat = call_mysql_query($sqlCat);
                $category_id = 0;
                if ($resCat && ($r = call_mysql_fetch_array($resCat))) {
                    $category_id = (int)$r['category_id'];
                }
                if ($category_id <= 0) {
                    $errors[] = 'Unknown category name in row ' . ($inserted + $updated + $skipped + 2);
                    $skipped++;
                    continue;
                }

                $item_description = $getCol($row, 'item_description');
                if ($item_description === '') {
                    $errors[] = 'Missing description in row ' . ($inserted + $updated + $skipped + 2);
                    $skipped++;
                    continue;
                }

                $quantity = _int($getCol($row, 'quantity'));
                $unit = $getCol($row, 'unit');
                $serial_number = sanitize_serial_number($getCol($row, 'serial_number'));
                $source_of_fund = $getCol($row, 'source_of_fund');
                $cost_value = _float_or_null($getCol($row, 'cost_value'));

                if ($quantity <= 0) $quantity = 1;
                if ($quantity !== 1) {
                    $errors[] = 'Row ' . $rowNo . ': quantity must be 1 for AST per-unit method (use one CSV row per item unit).';
                    $skipped++;
                    continue;
                }
                if ($unit === '') $unit = '';

                $is_available = _int($getCol($row, 'is_available'), 1) ? 1 : 0;

                if ($property_code_csv !== '') {
                    $property_code = $property_code_csv;
                    // Attempt to derive property number & series from property_code if missing
                    if ($property_number === '') {
                        $parts = explode('-', $property_code);
                        if (count($parts) >= 3) {
                            $property_number = sanitize_property_number($parts[1]);
                            if ($property_number !== '' && !is_valid_property_number($property_number)) {
                                $errors[] = 'Invalid property number in property code at row ' . ($inserted + $updated + $skipped + 2);
                                $skipped++;
                                continue;
                            }
                        }
                    }
                    if ($property_series_csv <= 0 && count(explode('-', $property_code)) >= 3) {
                        $last = explode('-', $property_code);
                        $property_series_csv = _int(end($last));
                    }
                } else {
                    if ($property_series_csv <= 0) {
                        if (!isset($seriesCache[$property_number])) {
                            $seriesCache[$property_number] = get_next_series($property_number) - 1;
                        }
                        $seriesCache[$property_number]++;
                        $property_series_csv = $seriesCache[$property_number];
                    }
                    $property_code = build_property_code($property_number, $property_series_csv);
                }

                $property_code_esc = _esc($property_code);
                $checkSql = "SELECT item_id, qr_image FROM ast_inventory WHERE property_code = '{$property_code_esc}' LIMIT 1";
                $checkRes = call_mysql_query($checkSql);

                if ($checkRes && ($existRow = call_mysql_fetch_array($checkRes))) {
                    $item_id = (int)$existRow['item_id'];
                    $qrImage = $existRow['qr_image'] ?? '';
                    if ($qrImage === '') {
                        $generated = generate_qr_image($property_code, 'ast_qr_', $UPLOAD_ABS_DIR);
                        if ($generated) {
                            $qrImage = $generated;
                        }
                    }
                    $updateSql = "UPDATE ast_inventory SET
                                    property_number = '" . _esc($property_number) . "',
                                    property_series = {$property_series_csv},
                                    category_id = {$category_id},
                                    item_description = '" . _esc($item_description) . "',
                                    " . ($hasSerial ? "serial_number = " . ($serial_number !== '' ? "'" . _esc($serial_number) . "'" : "NULL") . "," : "") . "
                                    quantity = 1,
                                    unit = '" . _esc($unit) . "',
                                    source_of_fund = " . ($source_of_fund !== '' ? "'" . _esc($source_of_fund) . "'" : "NULL") . ",
                                    cost_value = " . ($cost_value !== null ? "'" . _esc($cost_value) . "'" : "NULL") . ",
                                    " . ($qrImage !== '' ? "qr_image = '" . _esc($qrImage) . "'," : "") . "
                                    is_available = {$is_available}
                                  WHERE item_id = {$item_id}
                                  LIMIT 1";
                    call_mysql_query($updateSql);
                    $updated++;
                } else {
                    $qrImage = generate_qr_image($property_code, 'ast_qr_', $UPLOAD_ABS_DIR);
                    if (!$qrImage) {
                        $errors[] = 'Row ' . $rowNo . ': failed to generate QR image.';
                        $skipped++;
                        continue;
                    }
                    $insertSql = "INSERT INTO ast_inventory
                                    (property_number, property_series, property_code, category_id, item_description, " . ($hasSerial ? "serial_number, " : "") . "quantity, unit, source_of_fund, cost_value, qr_image, is_available)
                                  VALUES (
                                    '" . _esc($property_number) . "',
                                    {$property_series_csv},
                                    '" . _esc($property_code) . "',
                                    {$category_id},
                                    '" . _esc($item_description) . "',
                                    " . ($hasSerial ? ($serial_number !== '' ? "'" . _esc($serial_number) . "'" : "NULL") . "," : "") . "
                                    1,
                                    '" . _esc($unit) . "',
                                    " . ($source_of_fund !== '' ? "'" . _esc($source_of_fund) . "'" : "NULL") . ",
                                    " . ($cost_value !== null ? "'" . _esc($cost_value) . "'" : "NULL") . ",
                                    '" . _esc($qrImage) . "',
                                    {$is_available}
                                  )";
                    $ok = call_mysql_query($insertSql);
                    if ($ok) {
                        $inserted++;
                    } else {
                        if ($qrImage && file_exists($UPLOAD_ABS_DIR . $qrImage)) @unlink($UPLOAD_ABS_DIR . $qrImage);
                        $errors[] = 'Failed to insert row ' . $rowNo;
                        $skipped++;
                    }
                }
            }
            fclose($handle);

            json_response([
                'success' => true,
                'message' => 'Bulk upload complete.',
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors
            ]);
            break;

        case 'get_item_by_code':
            $property_code = _post('property_code');
            if ($property_code === '') {
                json_response(['success' => false, 'message' => 'Property code is required.'], 422);
            }
            $sql = "SELECT i.*, c.item_category_name
                    FROM ast_inventory i
                    LEFT JOIN ast_inventory_category c ON c.category_id = i.category_id
                    WHERE i.property_code = '" . _esc($property_code) . "' LIMIT 1";
            $res = call_mysql_query($sql);
            $row = $res ? call_mysql_fetch_array($res) : null;
            if (!$row) {
                json_response(['success' => false, 'message' => 'Item not found.'], 404);
            }
            if (!isset($row['serial_number'])) {
                $row['serial_number'] = '';
            }
            if (!empty($row['qr_image'])) {
                $row['qr_image_url'] = BASE_URL . $UPLOAD_REL_DIR . $row['qr_image'];
            } elseif (!empty($row['property_code'])) {
                $row['qr_image_url'] = BASE_URL . 'admin/modules/tools/qr_image.php?v=' . urlencode($row['property_code']);
            } else {
                $row['qr_image_url'] = null;
            }
            json_response(['success' => true, 'data' => $row]);
            break;

        case 'add_quantity':
            json_response([
                'success' => false,
                'message' => 'Add quantity is disabled for AST. Use Add New Item unit rows so each unit gets its own property code and QR.'
            ], 410);
            break;

        case 'toggle_available':
            $property_code = _post('property_code');
            $is_available = _int(_post('is_available')) ? 1 : 0;
            if ($property_code === '') {
                json_response(['success' => false, 'message' => 'Property code is required.'], 422);
            }
            $sql = "UPDATE ast_inventory SET is_available = {$is_available} WHERE property_code = '" . _esc($property_code) . "' LIMIT 1";
            $ok = call_mysql_query($sql);
            if (!$ok) {
                json_response(['success' => false, 'message' => 'Failed to update availability.'], 500);
            }
            activity_log_new("AST TOGGLE AVAILABLE", "SUCCESS", array(
                'property_code' => $property_code,
                'is_available' => $is_available
            ));
            json_response(['success' => true, 'message' => 'Availability updated.']);
            break;

        default:
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Exception $ex) {
    json_response(['success' => false, 'message' => 'Server error: ' . $ex->getMessage()], 500);
}
