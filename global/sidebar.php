<?php

/**
==================================================================
 File name   : sidebar.php
 Version     : 1.0.0
 Begin       : 2026-02-26
 Last Update :
 Author      :
 Description : sidebar (FOR ADMINS UI).
==================================================================
**/

$active_page = ACTIVE_PAGE;

if (!isset($g_user_role)) {
    include DOMAIN_PATH . "/404.php";
    exit();
}

function navigation_active($pages, $class = 'active', $conditions = array())
{
    global $active_page;

    $pageArray = array_map('trim', explode(',', $pages));
    if (!in_array($active_page, $pageArray)) {
        return '';
    }

    if (empty($conditions)) {
        return $class;
    }

    foreach ($conditions as $key => $values) {
        if (!isset($_GET[$key])) {
            return '';
        }
        $values = (array)$values;
        if (!in_array($_GET[$key], $values)) {
            return '';
        }
    }

    return $class;
}

function staff_has_any_access($codes)
{
    if (role_has("ADMIN")) {
        return true;
    }
    foreach ((array)$codes as $code) {
        if (user_has_access($code)) {
            return true;
        }
    }
    return false;
}

$staffHasAnyModuleAccess = false;
$staffHasPO = false;
$staffHasCSM = false;
$staffHasAST = false;
if (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) {
    $staffHasPO = user_has_access("PO");
    $staffHasCSM = user_has_access("CSM");
    $staffHasAST = user_has_access("AST");
    $staffHasAnyModuleAccess = ($staffHasPO || $staffHasCSM || $staffHasAST);
}
?>
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <div class="logo-header" data-background-color="dark">
            <a href="<?php echo BASE_URL . 'index.php'; ?>" class="logo pt-2">
                <img src="<?php echo DISPLAY_LOGO; ?>" alt="navbar brand" class="navbar-brand" height="100%">
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section"><?php echo ACCESS_NAME[$g_user_role] ?? $g_user_role; ?></h4>
                </li>

                <?php if (role_has("SUPER_ADMIN")) { ?>
                    <li class="nav-item <?php echo navigation_active("user_information"); ?>">
                        <a href="<?php echo BASE_URL . "superadmin/pages/user_information.php"; ?>">
                            <i class="fas fa-user"></i>
                            <p>User Information</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("school_info"); ?>">
                        <a href="<?php echo BASE_URL . "superadmin/pages/school_info.php"; ?>">
                            <i class="fas fa-school"></i>
                            <p>School Information</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("activity_logs"); ?>">
                        <a href="<?php echo BASE_URL . "superadmin/pages/activity_logs.php"; ?>">
                            <i class="fas fa-history"></i>
                            <p>Activity Logs</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("user_logs"); ?>">
                        <a href="<?php echo BASE_URL . "superadmin/pages/user_logs.php"; ?>">
                            <i class="fas fa-sign-in-alt"></i>
                            <p>User Logs</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("file_logs"); ?>">
                        <a href="<?php echo BASE_URL . "superadmin/pages/file_logs.php"; ?>">
                            <i class="fas fa-file-alt"></i>
                            <p>File Logs</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("system_backup"); ?>">
                        <a href="<?php echo BASE_URL . "superadmin/pages/system_backup.php"; ?>">
                            <i class="fas fa-database"></i>
                            <p>System Backup</p>
                        </a>
                    </li>
                <?php } ?>

                <?php if (role_has("ADMIN")) { ?>

                    <li class="nav-item <?php echo navigation_active("main_admin"); ?>">
                        <a href="<?php echo BASE_URL . "admin/dashboard/main_admin.php"; ?>">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                <?php } ?>

                <?php if ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && $staffHasAnyModuleAccess) { ?>
                    <li class="nav-item <?php echo navigation_active("main_admin_staff"); ?>">
                        <a href="<?php echo BASE_URL . "admin/dashboard/main_admin_staff.php"; ?>">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                <?php } ?>

                <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasCSM || $staffHasPO))) { ?>
                    <li class="nav-item <?php echo navigation_active("csm_category,csm_manage_inventory,csm_manage_invtest,csm_available_items,csm_physical_checking,csm_qrcode", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#csm_nav">
                            <i class="fas fa-cubes"></i>
                            <p>Consumable (CSM)</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("csm_category,csm_manage_inventory,csm_manage_invtest,csm_available_items,csm_physical_checking,csm_qrcode", "show"); ?>" id="csm_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("csm_category"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_category.php"; ?>">
                                        <span class="sub-item">Item Category</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("csm_manage_inventory"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_manage_inventory.php"; ?>">
                                        <span class="sub-item">Inventory</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("csm_available_items"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_available_items.php"; ?>">
                                        <span class="sub-item">Add New Item</span>
                                    </a>
                                </li>                                  
                                <li class="<?php echo navigation_active("csm_qrcode"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_qrcode.php"; ?>">
                                        <span class="sub-item">QR Code</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("csm_physical_checking"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_physical_checking.php"; ?>">
                                        <span class="sub-item">Physical Checking</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                <?php } ?>

                <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasAST || $staffHasPO))) { ?>
                    <li class="nav-item <?php echo navigation_active("ast_category,ast_inventory,ast_manage_inventory,ast_qrcode,ast_physical_checking", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#ast_nav">
                            <i class="fas fa-boxes"></i>
                            <p>Non-Consumable (AST)</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("ast_category,ast_inventory,ast_manage_inventory,ast_qrcode,ast_physical_checking", "show"); ?>" id="ast_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("ast_category"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_category.php"; ?>">
                                        <span class="sub-item">Item Category</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("ast_inventory"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_inventory.php"; ?>">
                                        <span class="sub-item">Inventory</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("ast_manage_inventory"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_manage_inventory.php"; ?>">
                                        <span class="sub-item">Add New Item</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("ast_qrcode"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_qrcode.php"; ?>">
                                        <span class="sub-item">QR Code</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("ast_physical_checking"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_physical_checking.php"; ?>">
                                        <span class="sub-item">Physical Checking</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                <?php } ?>

                <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasAST || $staffHasCSM))) { ?>
                    <li class="nav-item <?php echo navigation_active("requisition,manage_issuance,manage_returns", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#tx_nav">
                            <i class="fas fa-exchange-alt"></i>
                            <p>Transactions</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("requisition,manage_issuance,manage_returns", "show"); ?>" id="tx_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("requisition", "active", array("type" => array("AST", "CSM"))); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/transactions/requisition.php?type=AST"; ?>">
                                        <span class="sub-item">Requisition Item</span>
                                    </a>
                                </li>
                                <?php if (role_has("ADMIN") || $staffHasAST) { ?>
                                    <li class="<?php echo navigation_active("manage_issuance"); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/transactions/manage_issuance.php"; ?>">
                                            <span class="sub-item">Property Report</span>
                                        </a>
                                    </li>
                                    <li class="<?php echo navigation_active("manage_returns"); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/transactions/manage_returns.php"; ?>">
                                            <span class="sub-item">Property Return</span>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item <?php echo navigation_active("activity_logs"); ?>">
                        <a href="<?php echo BASE_URL . "admin/modules/logs/activity_logs.php"; ?>">
                            <i class="fas fa-list"></i>
                            <p>Activity Logs</p>
                        </a>
                    </li>
                <?php } ?>

                <?php if (role_has("USER") || role_has("USERS")) { ?>
                    <li class="nav-item <?php echo navigation_active("main_users"); ?>">
                        <a href="<?php echo BASE_URL . "app/main_users.php"; ?>">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("request_items"); ?>">
                        <a href="<?php echo BASE_URL . "app/request_items.php"; ?>">
                            <i class="fas fa-clipboard-list"></i>
                            <p>Request Items</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("facility_records"); ?>">
                        <a href="<?php echo BASE_URL . "app/facility_records.php"; ?>">
                            <i class="fas fa-building"></i>
                            <p>Facility Records</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("personal_records"); ?>">
                        <a href="<?php echo BASE_URL . "app/personal_records.php"; ?>">
                            <i class="fas fa-archive"></i>
                            <p>Personal Inventory</p>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>
