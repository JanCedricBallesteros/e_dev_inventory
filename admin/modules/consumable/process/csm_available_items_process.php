<?php
// process/csm_available_items_process.php
require_once dirname(__DIR__, 4) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json; charset=utf-8');

function json_out($ok, $data = null, $msg = ''){
    echo json_encode([
        'success' => (bool)$ok,
        'data'    => $data,
        'message' => $msg
    ]);
    exit;
}

function post($k, $d=''){
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d;
}
function post_int($k, $d=0){
    $v = post($k, '');
    if($v === '') return $d;
    return (int)$v;
}
function post_float($k, $d=0.0){
    $v = post($k, '');
    if($v === '') return $d;
    return (float)$v;
}

function normalize_file_url($u){
    $u = trim((string)$u);
    if($u === '') return '';
    $u = str_replace('\\','/',$u);
    while (strpos($u, '../') === 0) $u = substr($u, 3);
    $u = ltrim($u, '/'); // IMPORTANT so frontend absUrl works like manage_inventory.php
    return $u;
}

/**
 * THIS is the critical part:
 * Same logic as your working csm_manage_inventory.php
 */
function sql_inventory_with_display_image($whereSql = "", $limitSql = ""){
    $sql = "
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
        ORDER BY i.created_at DESC
        {$limitSql}
    ";
    return $sql;
}

function fetch_rows($sql){
    $res = call_mysql_query($sql);
    $rows = [];
    if($res){
        while($r = call_mysql_fetch_array($res)){
            $r['display_image'] = normalize_file_url($r['display_image'] ?? '');
            $r['assigned_image_url'] = normalize_file_url($r['assigned_image_url'] ?? '');
            $rows[] = $r;
        }
    }
    return $rows;
}

$action = post('action', '');

if($action === 'list_recent_added'){
    $sql = sql_inventory_with_display_image("", "LIMIT 100");
    $rows = fetch_rows($sql);
    json_out(true, $rows);
}

if($action === 'list_all_items'){
    $sql = sql_inventory_with_display_image("", "");
    $rows = fetch_rows($sql);
    json_out(true, $rows);
}

if($action === 'list_set_available'){
    // whatever filter you want; keeping all for now
    $sql = sql_inventory_with_display_image("", "");
    $rows = fetch_rows($sql);
    json_out(true, $rows);
}

if($action === 'find_item_by_code'){
    $code = post('inventory_system_item_code','');
    if($code === ''){
        json_out(false, null, 'Missing item code.');
    }

    // strict match (your system uses full code)
    $codeEsc = call_mysql_real_escape_string($code);
    $where = "WHERE i.inventory_system_item_code = '{$codeEsc}'";
    $sql = sql_inventory_with_display_image($where, "LIMIT 1");
    $rows = fetch_rows($sql);

    if(!$rows){
        json_out(false, null, 'Item not found.');
    }
    json_out(true, $rows[0]);
}

if($action === 'add_new_item'){
    $cat = post('item_category_code','');
    $desc = post('item_description','');
    $qty = post_int('unit_quantity', 0);
    $crit = post_int('unit_crit_level', 0);
    $unit = post('unit','');
    $cost = post_float('item_cost', 0);
    $funds = post('source_of_funds','');

    if($cat === '' || $desc === ''){
        json_out(false, null, 'Category and description are required.');
    }

    $catEsc  = call_mysql_real_escape_string($cat);
    $descEsc = call_mysql_real_escape_string($desc);
    $unitEsc = call_mysql_real_escape_string($unit);
    $fundEsc = call_mysql_real_escape_string($funds);

    // create next code per category: CSM-[CAT]-0001 style
    // Find max last 4 digits within this category
    $sqlMax = "
        SELECT MAX(CAST(RIGHT(inventory_system_item_code,4) AS UNSIGNED)) AS mx
        FROM csm_inventory
        WHERE item_category_code = '{$catEsc}'
          AND inventory_system_item_code LIKE CONCAT('CSM-', '{$catEsc}', '-%')
    ";
    $resMax = call_mysql_query($sqlMax);
    $mx = 0;
    if($resMax){
        $r = call_mysql_fetch_array($resMax);
        $mx = (int)($r['mx'] ?? 0);
    }
    $next = $mx + 1;
    $next4 = str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    $newCode = "CSM-{$cat}-{$next4}";
    $newCodeEsc = call_mysql_real_escape_string($newCode);

    // Insert (available starts = qty)
    // NOTE: acquisition_date/last_updated depends on your schema; if those columns exist, set them.
    // If not, remove them.
    $today = date('Y-m-d');
    $todayEsc = call_mysql_real_escape_string($today);

    // Build insert with optional columns that may exist.
    // If your DB doesn't have unit/acquisition_date/last_updated, remove them.
    $sqlIns = "
        INSERT INTO csm_inventory
        (inventory_system_item_code, item_category_code, item_description, unit_quantity, current_unit_quantity, unit_crit_level, item_cost, source_of_funds, unit, acquisition_date, last_updated, created_at)
        VALUES
        ('{$newCodeEsc}', '{$catEsc}', '{$descEsc}', {$qty}, {$qty}, {$crit}, {$cost}, '{$fundEsc}', '{$unitEsc}', '{$todayEsc}', '{$todayEsc}', NOW())
    ";

    $ok = call_mysql_query($sqlIns);
    if(!$ok){
        json_out(false, null, 'Insert failed.');
    }

    json_out(true, ['inventory_system_item_code' => $newCode], '',);
}

if($action === 'add_quantity'){
    $id = post_int('inventory_id', 0);
    $add = post_int('add_quantity', 0);
    $unit = post('unit','');
    $funds = post('source_of_funds','');
    $cost = post('item_cost',''); // can be blank to keep

    if($id <= 0 || $add <= 0){
        json_out(false, null, 'Invalid input.');
    }

    // load current
    $res = call_mysql_query("SELECT current_unit_quantity, item_cost, source_of_funds FROM csm_inventory WHERE inventory_id = {$id} LIMIT 1");
    if(!$res){
        json_out(false, null, 'Item not found.');
    }
    $r = call_mysql_fetch_array($res);
    if(!$r){
        json_out(false, null, 'Item not found.');
    }

    $curr = (int)($r['current_unit_quantity'] ?? 0);
    $new = $curr + $add;

    $sets = [];
    $sets[] = "current_unit_quantity = {$new}";
    $sets[] = "unit_quantity = unit_quantity + {$add}";
    $sets[] = "last_updated = CURDATE()";

    if(trim($unit) !== ''){
        $unitEsc = call_mysql_real_escape_string($unit);
        $sets[] = "unit = '{$unitEsc}'";
    }
    if(trim($funds) !== ''){
        $fundEsc = call_mysql_real_escape_string($funds);
        $sets[] = "source_of_funds = '{$fundEsc}'";
    }
    if(trim($cost) !== ''){
        $costVal = (float)$cost;
        $sets[] = "item_cost = {$costVal}";
    }

    $sqlUp = "UPDATE csm_inventory SET ".implode(", ", $sets)." WHERE inventory_id = {$id} LIMIT 1";
    $ok = call_mysql_query($sqlUp);
    if(!$ok){
        json_out(false, null, 'Update failed.');
    }

    json_out(true, true);
}

if($action === 'set_available_qty'){
    $id = post_int('inventory_id', 0);
    $val = post_int('current_unit_quantity', 0);
    if($id <= 0){
        json_out(false, null, 'Invalid item.');
    }

    // enforce rule: must be >= crit
    $res = call_mysql_query("SELECT unit_crit_level FROM csm_inventory WHERE inventory_id = {$id} LIMIT 1");
    $r = $res ? call_mysql_fetch_array($res) : null;
    if(!$r){
        json_out(false, null, 'Item not found.');
    }
    $crit = (int)($r['unit_crit_level'] ?? 0);
    if($val < $crit){
        json_out(false, null, "Cannot set available below critical level ({$crit}).");
    }

    $ok = call_mysql_query("UPDATE csm_inventory SET current_unit_quantity = {$val}, last_updated = CURDATE() WHERE inventory_id = {$id} LIMIT 1");
    if(!$ok){
        json_out(false, null, 'Update failed.');
    }
    json_out(true, true);
}

json_out(false, null, 'Invalid action.');