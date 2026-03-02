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

if (role_has("SUPER_ADMIN") || role_has("ADMIN")) {
	header("Location: " . BASE_URL . "admin/modules/nonconsumable/ast_inventory.php");
	exit();
}

if (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) {
	if (user_has_access(array("AST", "PO"))) {
		header("Location: " . BASE_URL . "admin/modules/nonconsumable/ast_inventory.php");
		exit();
	}
	if (user_has_access("CSM")) {
		header("Location: " . BASE_URL . "admin/modules/consumable/csm_manage_inventory.php");
		exit();
	}
	header("Location: " . BASE_URL . "admin/modules/transactions/requisition.php?type=AST");
	exit();
}

if (role_has("USER") || role_has("USERS")) {
	header("Location: " . BASE_URL . "app/main.php");
	exit();
}

header("Location: " . API_URL);
exit();
