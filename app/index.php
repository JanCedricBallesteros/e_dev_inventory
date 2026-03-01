<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

$page_id = "";
$error_encounter = false;

## change based on roles [change also in other role files or folder]
if (isset($g_user_role) || !empty($g_user_role)) {
	if ($g_user_role == "SUPER_ADMIN") {
		header("Location: " . BASE_URL . "admin/main_admin.php");
		exit();
	} else if ($g_user_role == "ADMIN") {
		header("Location: " . BASE_URL . "app/main.php");
		exit();
	} else if ($g_user_role == "ADMIN_STAFF") {
		header("Location: " . BASE_URL . "app/main.php");
		exit();
	} else if ($g_user_role == "USER") {
		header("Location: " . BASE_URL . "app/main.php");
		exit();
	} else {
		header("Location: " . API_URL);
		exit();
	}
} else {
	header("Location: " . API_URL);
	exit();
}