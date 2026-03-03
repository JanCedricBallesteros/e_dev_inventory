<?php
require_once("read_envi.php");

## default settings values
$before_memory = 0;
$config['filesize_limit'] = 20971520; //50000000;//20MB FILE UPLOAD LIMIT
$close_conn = true;

defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__));
define("LANG", 'en');
define("META_AUTHOR", '');
define("META_DESC", '');

## datetime zone
// date_default_timezone_set('UTC');
define("DEFAULT_TIMEZONE", 'Asia/Manila');
ini_set('date.timezone', DEFAULT_TIMEZONE);
date_default_timezone_set(DEFAULT_TIMEZONE);

## dates
define('YEAR', date('Y'));
define('MONTH', date('m'));
define('DAY', date('d'));
define('DATE_NOW', date('Y-m-d'));
define('TIME_NOW', date('H:i:s'));
define("DATE_TIME", DATE_NOW . " " . TIME_NOW);

## school info
define('SCHOOL_NAME', 'City College of Calamba');
define('SCHOOL_ACRONYM', 'CCC');
define('SCHOOL_ADDRESS', '');

## system info
define('SYSTEM_NAME', 'e-Inventory Management System');
define('SYSTEM_SUB_NAME', 'e-Inventory');
define('SYSTEM_TEAM', 'MISD Team');
define('YEAR_CREATED', '2026');
define('PAGE_TITLE', 'City College Calamba');
define('FILE_VERSION', '1.0.0');

## active link
$active_page = active_page();
define("ACTIVE_PAGE", $active_page);

## SYSTEM ACCESS LINKs [system_url("local_domain","web_domain")]
$url = system_url("e_dev_inventory", "e_dev_inventory.com");
$api_url = system_url("e_dev_eguro", "e_dev_eguro.com");
define("BASE_URL", $url);
define("API_URL", $api_url);

## SYSTEM ACCESS
function system_links($base_url, $include_user_mgmt = true)
{
    $links = [
        'main'   => $base_url . 'auth-file/login.php',
        'second' => '',
        'logout' => $base_url . 'auth-file/logout.php',
    ];

    if ($include_user_mgmt) {
        $links += [
            'create_user' => $base_url . 'auth-file/create_user.php',
            'update_user' => $base_url . 'auth-file/update_user.php',
            'bulk_user'   => $base_url . 'auth-file/bulk_user.php',
            'add_account' => $base_url . 'auth-file/add_account.php',
        ];
    }

    return $links;
}

$system_access = [
    'E-GURO++' => [
        'name'       => 'e-GURO++',
        'short_name' => 'e-GURO++',
        'role'       => ['1' => 'ADMIN', '2' => 'REGISTRAR', '3' => 'VPAA'], ## change based on need
        'link' => [
            'main' => API_URL . 'app/index.php',
            'second' => API_URL . 'admin/index.php',
            'create_user' => '',
            'password' => API_URL . 'auth-file/password.php'
        ],
        'auth'       => 'eguro_auth_login'
    ],

    'E-INVENTORY' => [
        'name'       => 'e-Inventory Management System',
        'short_name' => 'e-Inventory',
        'role'       => ['1' => 'SUPER_ADMIN', '2' => 'ADMIN', '3' => 'ADMIN_STAFF', '4' => 'USER'], ## change based on need
        'link'       => system_links(BASE_URL),
        'access'     => [],
        'max_user'   => 1,
        'auth'       => 'e_inventory_auth_login'
    ],
];
define('SYSTEM_ACCESS', $system_access);
define('GLOBAL_SYSTEM_ACCESS', 'E-INVENTORY');

## user access name [change based on need]
$user_access_name = [
    'SUPER_ADMIN' => "Super Administrator",
    'ADMIN' => "Administrator",
    'ADMIN_STAFF' => "Administrator Staff",
    'USER' => "User",
];
define('ACCESS_NAME', $user_access_name);

## logo path
define("LOGO", BASE_URL . 'upload/img/inventory-logo-white.png?v=' . FILE_VERSION);
define("DISPLAY_LOGO", BASE_URL . 'upload/img/inventory-logo-white.png?v=' . FILE_VERSION);
define("FAVICON", BASE_URL . 'upload/img/favicon.ico?v=' . FILE_VERSION);

## csv maximum file
define('CSV_SIZE', (50 * 1024 * 1024));

## limit fetch data
define('QUERY_LIMIT', 20);

## function path
define('CL_SESSION_PATH', DOMAIN_PATH . '/call_func/cl_session.php');
define('CONNECT_PATH', DOMAIN_PATH . '/call_func/connect.php');
define('VALIDATOR_PATH', DOMAIN_PATH . '/call_func/validator.php');
define('GLOBAL_FUNC', DOMAIN_PATH . '/call_func/global_func.php');
define('ISLOGIN', DOMAIN_PATH . '/call_func/islogin.php');
define('ALERT_SESSION', DOMAIN_PATH . '/call_func/alert_session.php');
define('PASSWORD_HELPER', DOMAIN_PATH . '/call_func/password_helper.php');
define('API_PATH', DOMAIN_PATH . '/config/api_data.php');
define('JWT_PATH', DOMAIN_PATH . '/jwt/autoload.php');
define('UPLOAD_HANDLER', DOMAIN_PATH . '/call_func/UploaderHandler.php');
define('FOOTER_PATH', DOMAIN_PATH . '/global/footer.php');

## global path
define('META_PATH', DOMAIN_PATH . '/global/meta_data.php');
define('SIDEBAR_PATH', DOMAIN_PATH . '/global/sidebar.php');
define('HEADER_PATH', DOMAIN_PATH . '/global/header.php');
define('PAGE_HEADER_PATH', DOMAIN_PATH . '/global/page_header.php');
define('LINK_PATH', DOMAIN_PATH . '/global/include_top.php');
define('SCRIPT_PATH', DOMAIN_PATH . '/global/include_bottom.php');

## upload path
define("UPLOAD_FILE_PATH", DOMAIN_PATH . '/upload/file/');
define("UPLOAD_IMG_PATH", DOMAIN_PATH . '/upload/img/');
define('LOGS_PATH', DOMAIN_PATH . '/upload/logs/');
define('CSV_PATH', DOMAIN_PATH . '/upload/csv/');

## img path
define("IMG_PATH", BASE_URL . 'upload/img/');
define("FILE_PATH", BASE_URL . 'upload/file/');

## guide path

## status
$g_account_status = [
    "0" => "Active",
    "1" => "Suspended",
    "2" => "Archived",
];
define("ACCOUNT_STATUS", $g_account_status);

## default
$default_session = (SYSTEM_FLAG === 'DEV') ? 'e_dev_inventory_session' : 'e_inventory_session';
define('DEFAULT_SESSION', $default_session);
define('SESSION_CONFIG', array('name' => DEFAULT_SESSION, 'path' => '/', 'domain' => '', 'secure' => false, 'bits' => 4, 'length' => 32, 'hash' => 'sha256', 'decoy' => true, 'min' => 300, 'max' => 800, 'debug' => false));
define('SALT', '895012342025TREWPOIUYT_'); // change me
## for $csrf->string
define('MAX_HASHES_STRING', 15);
define('TIME_STRING', -1);
## for img
define('DEFAULT_IMG', 'profile-img.png');

## encrypted || decrypted
define('CRYPT_CIPHER', 'aes-256-cbc');
define('CRYPT_DIRTY', array("+", "/", "="));
define('CRYPT_CLEAN', array("_PLUS_", "_SLASH_", "_EQUALS_"));

## other constant variable

## default img
define('IMG_DEFAULT', 'profile-img.png');

## http errors
define("HTTP_401", DOMAIN_PATH . "/error_page/401.php");
define("HTTP_404", DOMAIN_PATH . "/error_page/404.php");

if (SYSTEM_FLAG === 'DEV') {
    ifexist_ini_set("display_errors", 1);
    //error_reporting(-1);
    error_reporting(E_ALL ^ E_NOTICE);
    ifexist_ini_set("error_log", join(DIRECTORY_SEPARATOR, array(LOGS_PATH, "php-error.log")));
    $before_memory = print_mem();
} else {
    ifexist_ini_set('display_errors', 0);
    //ifexist_ini_set("log_errors", 1);
    error_reporting(E_ALL & ~E_NOTICE);
    ifexist_ini_set("error_log", join(DIRECTORY_SEPARATOR, array(LOGS_PATH, "php-error.log")));
}

## function
function ifexist_ini_set($func, $key)
{
    if (!function_exists('ini_set')) {
        return;
    }
    ini_set($func, $key);
}

function mem_convert($size)
{
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

function print_mem()
{
    /* Currently used memory */
    $mem_usage = memory_get_usage();

    /* Peak memory usage */
    $mem_peak = memory_get_peak_usage();

    $return = 'The script is now using: <strong>' . mem_convert($mem_usage) . '</strong> of memory.<br>';
    $return .=  'Peak usage: <strong>' . mem_convert($mem_peak) . '</strong> of memory.<br><br>';

    return $return;
}

function page_url()
{
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    return $actual_link;
}

function active_page()
{
    $actual_link = basename($_SERVER['PHP_SELF'], ".php");
    return $actual_link;
}


## links
function get_protocol()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
}

function system_url($local_domain, $web_domain)
{
    $protocol = get_protocol();
    $host = $_SERVER['HTTP_HOST'];

    if (defined('SYSTEM_FLAG') && SYSTEM_FLAG === 'DEV') {
        return $protocol . $host . '/' . $local_domain . '/';
    } else {
        return $protocol . $web_domain . '/';
    }
}

// function api_url()
// {
//     $protocol = get_protocol();
//     $host = $_SERVER['HTTP_HOST'];

//     if (defined('SYSTEM_FLAG') && SYSTEM_FLAG === 'DEV') {
//         return $protocol . $host . '/eguro/';
//     } else {
//         return $protocol . 'eguro.com/';
//     }
// }

// function base_url()
// {
//     $protocol = get_protocol();
//     $host = $_SERVER['HTTP_HOST'];

//     if (defined('SYSTEM_FLAG') && SYSTEM_FLAG === 'DEV') {
//         return $protocol . $host . '/dev_website/';
//     } else {
//         return $protocol . 'ccc.com/';
//     }
// }
