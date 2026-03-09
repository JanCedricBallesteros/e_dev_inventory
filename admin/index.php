<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

if (!isset($g_user_role) || empty($g_user_role)) {
	header("Location: " . API_URL);
	exit();
}

if (role_has("SUPER_ADMIN")) {
	header("Location: " . BASE_URL . "superadmin/pages/user_information.php");
	exit();
}

if (role_has("ADMIN")) {
	header("Location: " . BASE_URL . "admin/dashboard/main_admin.php");
	exit();
}

if (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) {
	header("Location: " . BASE_URL . "admin/dashboard/main_admin_staff.php");
	exit();
}

if (role_has("USER") || role_has("USERS")) {
	header("Location: " . BASE_URL . "app/main_users.php");
	exit();
}

header("Location: " . API_URL);
exit();
