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
$sidebarShouldShowBadges = role_has("ADMIN") || role_has("ADMIN_STAFF") || role_has("ADMINSTAFF");
if (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) {
    $staffHasPO = user_has_access("PO");
    $staffHasCSM = user_has_access("CSM");
    $staffHasAST = user_has_access("AST");
    $staffHasAnyModuleAccess = ($staffHasPO || $staffHasCSM || $staffHasAST);
}

function sidebar_count_query($sql)
{
    $res = call_mysql_query($sql);
    if (!$res) return 0;
    $row = call_mysql_fetch_array($res);
    return (int)($row['cnt'] ?? 0);
}

function sidebar_table_exists($table)
{
    $res = call_mysql_query("SHOW TABLES LIKE '" . addslashes((string)$table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function sidebar_column_exists($table, $column)
{
    $res = call_mysql_query("SHOW COLUMNS FROM `" . str_replace('`', '``', (string)$table) . "` LIKE '" . addslashes((string)$column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function sidebar_badge_html($count, $class = 'badge-danger')
{
    $n = (int)$count;
    if ($n <= 0) return '';
    return '<span class="badge badge-count ' . $class . ' ms-2">' . $n . '</span>';
}

function sidebar_action_badge_total($a = 0, $b = 0, $c = 0)
{
    return ((int)$a + (int)$b + (int)$c);
}

function sidebar_admin_staff_without_access_total()
{
    if (!sidebar_table_exists('users') || !sidebar_table_exists('user_access')) {
        return 0;
    }

    $res = call_mysql_query("SELECT u.user_id, u.user_role, COUNT(ua.user_access_id) AS active_access_total
                             FROM users u
                             LEFT JOIN user_access ua ON ua.user_id = u.user_id AND ua.is_active = 1
                             GROUP BY u.user_id, u.user_role");
    if (!$res) {
        return 0;
    }

    $total = 0;
    while ($row = call_mysql_fetch_array($res)) {
        $role_ids = json_decode($row['user_role'] ?? '[]', true);
        $role_ids = is_array($role_ids) ? $role_ids : array();
        $isAdminStaff = in_array(3, $role_ids, true) || in_array('3', $role_ids, true);
        if ($isAdminStaff && (int)($row['active_access_total'] ?? 0) === 0) {
            $total++;
        }
    }

    return $total;
}

$adminCsmReqPending = 0;
$adminAstReqPending = 0;
$adminReqPendingTotal = 0;
$adminReqForClaimingTotal = 0;
$adminReqNotClaimedTotal = 0;
$adminReturnRequested = 0;
$adminFacilityReported = 0;
$adminFacilityAttentionTotal = 0;
$adminCsmCritical = 0;
$adminCsmOutOfStock = 0;
$adminCsmInventoryAttention = 0;
$adminCsmAuditActive = 0;
$adminAstAuditActive = 0;
$adminCsmMainBadge = 0;
$adminAstMainBadge = 0;
$adminTxMainBadge = 0;
$superAdminStaffNoAccessTotal = 0;
if ($sidebarShouldShowBadges) {
    if (sidebar_table_exists('requisition_items') && sidebar_column_exists('requisition_items', 'module_type') && sidebar_column_exists('requisition_items', 'status')) {
        $adminCsmReqPending = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                   FROM requisition_items
                                                   WHERE module_type = 'CSM'
                                                     AND status IN ('pending', 'reviewed')");
        $adminAstReqPending = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                   FROM requisition_items
                                                   WHERE module_type = 'AST'
                                                     AND status IN ('pending', 'reviewed')");
        $adminReqPendingTotal = $adminCsmReqPending + $adminAstReqPending;
        $adminReqForClaimingTotal = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                         FROM requisition_items
                                                         WHERE status = 'approved'");
        $adminReqNotClaimedTotal = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                        FROM requisition_items
                                                        WHERE status = 'not_claimed'");
    }
    if (sidebar_table_exists('facility_records_assignments') && sidebar_column_exists('facility_records_assignments', 'status')) {
        $adminReturnRequested = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                     FROM facility_records_assignments
                                                     WHERE status = 'RETURN_REQUESTED'");
        $adminFacilityReported = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                      FROM facility_records_assignments
                                                      WHERE status = 'REPORTED'");
        $adminFacilityAttentionTotal = $adminReturnRequested + $adminFacilityReported;
    }
    if (sidebar_table_exists('csm_inventory')) {
        $qtyCol = '';
        if (sidebar_column_exists('csm_inventory', 'current_unit_quantity')) {
            $qtyCol = 'current_unit_quantity';
        } elseif (sidebar_column_exists('csm_inventory', 'quantity')) {
            $qtyCol = 'quantity';
        }
        if ($qtyCol !== '' && sidebar_column_exists('csm_inventory', 'qty_crit_level')) {
            $adminCsmCritical = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                     FROM csm_inventory
                                                     WHERE {$qtyCol} > 0
                                                       AND {$qtyCol} <= qty_crit_level");
        }
        if ($qtyCol !== '') {
            $adminCsmOutOfStock = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                       FROM csm_inventory
                                                       WHERE {$qtyCol} <= 0");
        }
        $adminCsmInventoryAttention = $adminCsmCritical + $adminCsmOutOfStock;
    }
    if (sidebar_table_exists('csm_audit_sessions') && sidebar_column_exists('csm_audit_sessions', 'status')) {
        $adminCsmAuditActive = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                    FROM csm_audit_sessions
                                                    WHERE status IN ('active','ongoing','open','in_progress')");
    }
    if (sidebar_table_exists('ast_audit_sessions') && sidebar_column_exists('ast_audit_sessions', 'status')) {
        $adminAstAuditActive = sidebar_count_query("SELECT COUNT(*) AS cnt
                                                    FROM ast_audit_sessions
                                                    WHERE status IN ('active','ongoing','open','in_progress')");
    }
    /*
     * Keep parent-group badges consistent with the currently visible
     * sub-item badges inside each group.
     */
    $adminCsmMainBadge = $adminCsmInventoryAttention + $adminCsmAuditActive;
    $adminAstMainBadge = $adminFacilityReported + $adminAstAuditActive;
    $adminTxMainBadge = $adminReqPendingTotal + $adminReturnRequested + $adminFacilityAttentionTotal;
    $superAdminStaffNoAccessTotal = sidebar_admin_staff_without_access_total();
}
?>
<style>
    /* Sidebar notification badges: keep compact/fixed look with no white border */
    .sidebar .nav .badge.badge-count {
        border: 0 !important;
        box-shadow: none !important;
        min-width: 20px;
        height: 20px;
        line-height: 20px;
        padding: 0 6px;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        border-radius: 999px;
        vertical-align: middle;
        flex: 0 0 auto;
    }
    .sidebar .nav .sub-item .badge.badge-count,
    .sidebar .nav p .badge.badge-count {
        margin-left: 6px !important;
    }
    .sidebar .nav .nav-item a:hover .badge.badge-count,
    .sidebar .nav .nav-item.active a .badge.badge-count,
    .sidebar .nav .nav-item .collapse .active a .badge.badge-count {
        transform: none !important;
        width: auto !important;
        max-width: none !important;
    }
    .sidebar .nav .sidebar-group-badge {
        display: inline-flex !important;
        position: absolute !important;
        top: 50% !important;
        right: 6px !important; /* push badge more to the right in expanded mode */
        left: auto !important;
        transform: translateY(-50%) !important;
        margin: 0 !important;
        z-index: 3;
    }
    .sidebar .nav .nav-item > a {
        position: relative;
    }
    /* Collapsed sidebar: place badge in item corner so it doesn't cover the icon */
    .sidebar_minimize .sidebar .sidebar-wrapper .nav-item > a .sidebar-group-badge {
        top: 6px !important;
        right: 6px !important;
        transform: none !important;
        min-width: 16px;
        height: 16px;
        line-height: 16px;
        padding: 0 4px;
        font-size: 10px;
    }
    /* Hover-expanded from collapsed: restore right-side centered placement */
    .sidebar_minimize.sidebar_minimize_hover .sidebar .sidebar-wrapper .nav-item > a .sidebar-group-badge {
        top: 50% !important;
        right: 6px !important;
        transform: translateY(-50%) !important;
        min-width: 20px;
        height: 20px;
        line-height: 20px;
        padding: 0 6px;
        font-size: inherit;
    }
</style>
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
                            <p>User Information<?php echo sidebar_badge_html($superAdminStaffNoAccessTotal, 'badge-warning'); ?></p>
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
                    <li class="nav-item <?php echo navigation_active("inventory_audit"); ?>">
                        <a href="<?php echo BASE_URL . "admin/modules/audit/inventory_audit.php"; ?>">
                            <i class="fas fa-clipboard-check"></i>
                            <p>Inventory Audit</p>
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

                <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasPO))) { ?>
                    <li class="nav-item <?php echo navigation_active("csm_category,csm_manage_inventory,csm_manage_invtest,csm_available_items,csm_physical_checking,csm_qrcode", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#csm_nav">
                            <i class="fas fa-cubes"></i>
                            <span class="sidebar-group-badge"><?php echo sidebar_badge_html($adminCsmMainBadge); ?></span>
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
                                        <span class="sub-item">Inventory<?php echo sidebar_badge_html($adminCsmInventoryAttention, 'badge-warning'); ?></span>
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
                                <?php if (role_has("ADMIN")) { ?>
                                    <li class="<?php echo navigation_active("csm_physical_checking"); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_physical_checking.php"; ?>">
                                            <span class="sub-item">Physical Checking<?php echo sidebar_badge_html($adminCsmAuditActive, 'badge-info'); ?></span>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </li>
                <?php } ?>

                <?php if ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasCSM)) { ?>
                    <li class="nav-item <?php echo navigation_active("csm_category,csm_manage_inventory,csm_manage_invtest,csm_available_items,csm_physical_checking,csm_qrcode", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#csm_nav">
                            <i class="fas fa-cubes"></i>
                            <span class="sidebar-group-badge"><?php echo sidebar_badge_html($adminCsmMainBadge); ?></span>
                            <p>Consumable (CSM)</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("csm_category,csm_manage_inventory,csm_manage_invtest,csm_available_items,csm_physical_checking,csm_qrcode", "show"); ?>" id="csm_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("csm_manage_inventory"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_manage_inventory.php"; ?>">
                                        <span class="sub-item">Inventory<?php echo sidebar_badge_html($adminCsmInventoryAttention, 'badge-warning'); ?></span>
                                    </a>
                                </li>                               
                                <li class="<?php echo navigation_active("csm_qrcode"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_qrcode.php"; ?>">
                                        <span class="sub-item">QR Code</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("csm_physical_checking"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/consumable/csm_physical_checking.php"; ?>">
                                        <span class="sub-item">Physical Checking<?php echo sidebar_badge_html($adminCsmAuditActive, 'badge-info'); ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                <?php } ?>

                <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasAST || $staffHasPO))) { ?>
                    <li class="nav-item <?php echo navigation_active("ast_category,ast_inventory,ast_summary_report,ast_manage_inventory,ast_qrcode,ast_physical_checking,ast_issuance", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#ast_nav">
                            <i class="fas fa-boxes"></i>
                            <span class="sidebar-group-badge"><?php echo sidebar_badge_html($adminAstMainBadge); ?></span>
                            <p>Non-Consumable (AST)</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("ast_category,ast_inventory,ast_summary_report,ast_manage_inventory,ast_qrcode,ast_physical_checking,ast_issuance", "show"); ?>" id="ast_nav">
                            <ul class="nav nav-collapse">
                                <?php if (role_has("ADMIN") || $staffHasPO) { ?>
                                    <li class="<?php echo navigation_active("ast_category"); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_category.php"; ?>">
                                            <span class="sub-item">Item Category</span>
                                        </a>
                                    </li>
                                <?php } ?>
                                <li class="<?php echo navigation_active("ast_inventory"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_inventory.php"; ?>">
                                        <span class="sub-item">Inventory</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("ast_summary_report"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_summary_report.php"; ?>">
                                        <span class="sub-item">Property Report<?php echo sidebar_badge_html($adminFacilityReported, 'badge-warning'); ?></span>
                                    </a>
                                </li>
                                <?php if (role_has("ADMIN") || $staffHasPO) { ?>
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
                                <?php } ?>
                                <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && $staffHasAST)) { ?>
                                    <li class="<?php echo navigation_active("ast_physical_checking"); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_physical_checking.php"; ?>">
                                            <span class="sub-item">Physical Checking<?php echo sidebar_badge_html($adminAstAuditActive, 'badge-info'); ?></span>
                                        </a>
                                    </li>
                                <?php } ?>
                                <?php if ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasAST)) { ?>
                                    <li class="<?php echo navigation_active("ast_issuance"); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/nonconsumable/ast_issuance.php"; ?>">
                                            <span class="sub-item">Issuance</span>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </li>
                <?php } ?>

                <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && ($staffHasAST || $staffHasCSM || $staffHasPO))) { ?>
                    <li class="nav-item <?php echo navigation_active("requisition,manage_returns,facility_inventory_records", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#tx_nav">
                            <i class="fas fa-exchange-alt"></i>
                            <span class="sidebar-group-badge"><?php echo sidebar_badge_html($adminTxMainBadge); ?></span>
                            <p>Transactions</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("requisition,manage_returns,facility_inventory_records", "show"); ?>" id="tx_nav">
                            <ul class="nav nav-collapse">
                                <?php if (role_has("ADMIN") || $staffHasAST) { ?>
                                    <li class="<?php echo navigation_active("manage_returns"); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/transactions/manage_returns.php"; ?>">
                                            <span class="sub-item">Property Return<?php echo sidebar_badge_html($adminReturnRequested, 'badge-warning'); ?></span>
                                        </a>
                                    </li>
                                <?php } ?>
                                <?php if (role_has("ADMIN") || $staffHasAST || $staffHasCSM) { ?>
                                    <li class="<?php echo navigation_active("requisition", "active", array("type" => array("AST", "CSM"))); ?>">
                                        <a href="<?php echo BASE_URL . "admin/modules/transactions/requisition.php?type=" . ($staffHasCSM && !$staffHasAST ? "CSM" : "AST"); ?>">
                                            <span class="sub-item">Requisition Item<?php echo sidebar_badge_html($adminReqPendingTotal, 'badge-primary'); ?></span>
                                        </a>
                                    </li>
                                <?php } ?>
                                <li class="<?php echo navigation_active("facility_inventory_records"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/modules/transactions/facility_inventory_records.php"; ?>">
                                        <span class="sub-item">Facility Inventory Records<?php echo sidebar_badge_html($adminFacilityAttentionTotal, 'badge-warning'); ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <?php if (role_has("ADMIN") || ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && $staffHasCSM)) { ?>
                        <li class="nav-item <?php echo navigation_active("activity_logs"); ?>">
                            <a href="<?php echo BASE_URL . "admin/modules/logs/activity_logs.php"; ?>">
                                <i class="fas fa-list"></i>
                                <p>Activity Logs</p>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (role_has("ADMIN")) { ?>
                        <li class="nav-item <?php echo navigation_active("user_information"); ?>">
                            <a href="<?php echo BASE_URL . "superadmin/pages/user_information.php"; ?>">
                                <i class="fas fa-users"></i>
                                <p>Staff Information<?php echo sidebar_badge_html($superAdminStaffNoAccessTotal, 'badge-warning'); ?></p>
                            </a>
                        </li>
                    <?php } ?>
                <?php } ?>

                <?php if (role_has("USER") || role_has("USERS")) { ?>
                    <li class="nav-item <?php echo navigation_active("main_users"); ?>">
                        <a href="<?php echo BASE_URL . "users/dashboard/main_users.php"; ?>">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo navigation_active("request_items"); ?>">
                        <a href="<?php echo BASE_URL . "users/modules/request_items.php"; ?>">
                            <i class="fas fa-clipboard-list"></i>
                            <p>Request Items</p>
                        </a>
                    </li>
                    <?php if (user_has_managing_facility_unit()) { ?>
                        <li class="nav-item <?php echo navigation_active("facility_records"); ?>">
                            <a href="<?php echo BASE_URL . "users/modules/facility_records.php"; ?>">
                                <i class="fas fa-building"></i>
                                <p>Facility Records</p>
                            </a>
                        </li>
                    <?php } ?>
                    <li class="nav-item <?php echo navigation_active("personal_records"); ?>">
                        <a href="<?php echo BASE_URL . "users/modules/personal_records.php"; ?>">
                            <i class="fas fa-archive"></i>
                            <p>Personal Inventory</p>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>

