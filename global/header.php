<?php

/** 
==================================================================
 File name   : header.php
 Version     : 1.0.0
 Begin       : 2026-02-26
 Last Update : 
 Author      : 
 Description : main header (FOR ADMINS UI).
 =================================================================
 **/

## remove some section if not needed

function topbar_count_query($sql)
{
    $res = call_mysql_query($sql);
    if (!$res) return 0;
    $row = call_mysql_fetch_array($res);
    return (int)($row['cnt'] ?? 0);
}

function topbar_table_exists($table)
{
    $res = call_mysql_query("SHOW TABLES LIKE '" . addslashes((string)$table) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function topbar_column_exists($table, $column)
{
    $res = call_mysql_query("SHOW COLUMNS FROM `" . str_replace('`', '``', (string)$table) . "` LIKE '" . addslashes((string)$column) . "'");
    return $res && mysqli_num_rows($res) > 0;
}

$topbarNotifications = [];
$topbarNotifTotal = 0;

if (role_has("ADMIN")) {
    if (topbar_table_exists('requisition_items') && topbar_column_exists('requisition_items', 'status') && topbar_column_exists('requisition_items', 'module_type')) {
        $cntPendingAll = topbar_count_query("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status IN ('pending','reviewed')");
        $cntPendingAst = topbar_count_query("SELECT COUNT(*) AS cnt FROM requisition_items WHERE module_type='AST' AND status IN ('pending','reviewed')");
        $cntPendingCsm = topbar_count_query("SELECT COUNT(*) AS cnt FROM requisition_items WHERE module_type='CSM' AND status IN ('pending','reviewed')");

        if ($cntPendingAst > 0) {
            $topbarNotifications[] = [
                'icon_class' => 'notif-info',
                'icon' => 'fa fa-boxes',
                'text' => $cntPendingAst . ' AST requisition(s) pending',
                'url' => BASE_URL . 'admin/modules/transactions/requisition.php?type=AST'
            ];
        }
        if ($cntPendingCsm > 0) {
            $topbarNotifications[] = [
                'icon_class' => 'notif-success',
                'icon' => 'fa fa-cubes',
                'text' => $cntPendingCsm . ' CSM requisition(s) pending',
                'url' => BASE_URL . 'admin/modules/transactions/requisition.php?type=CSM'
            ];
        }
    }

    if (topbar_table_exists('facility_records_assignments') && topbar_column_exists('facility_records_assignments', 'status')) {
        $cntReturnRequested = topbar_count_query("SELECT COUNT(*) AS cnt FROM facility_records_assignments WHERE status='RETURN_REQUESTED'");
        if ($cntReturnRequested > 0) {
            $topbarNotifications[] = [
                'icon_class' => 'notif-warning',
                'icon' => 'fa fa-undo',
                'text' => $cntReturnRequested . ' property return request(s)',
                'url' => BASE_URL . 'admin/modules/transactions/manage_returns.php'
            ];
        }
    }
}

if ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && user_has_access("AST")) {
    if (topbar_table_exists('requisition_items') && topbar_column_exists('requisition_items', 'status') && topbar_column_exists('requisition_items', 'module_type')) {
        $cntForClaimingAst = topbar_count_query("SELECT COUNT(*) AS cnt FROM requisition_items WHERE module_type='AST' AND status='approved'");
        if ($cntForClaimingAst > 0) {
            $topbarNotifications[] = [
                'icon_class' => 'notif-primary',
                'icon' => 'fa fa-truck-loading',
                'text' => $cntForClaimingAst . ' AST request(s) ready for issuance',
                'url' => BASE_URL . 'admin/modules/nonconsumable/ast_issuance.php'
            ];
        }
    }
}

$topbarNotifTotal = count($topbarNotifications);

function topbar_add_search_item(&$items, $title, $url, $keywords = '', $section = '')
{
    $items[] = [
        'title' => $title,
        'url' => $url,
        'keywords' => $keywords,
        'section' => $section,
    ];
}

$topbarSearchItems = [];

if (role_has("SUPER_ADMIN")) {
    topbar_add_search_item($topbarSearchItems, 'User Information', BASE_URL . 'superadmin/pages/user_information.php', 'users staff access employment status teaching non teaching permanent cos job order', 'Super Admin');
    topbar_add_search_item($topbarSearchItems, 'School Information', BASE_URL . 'superadmin/pages/school_info.php', 'school report forms information', 'Super Admin');
    topbar_add_search_item($topbarSearchItems, 'Activity Logs', BASE_URL . 'superadmin/pages/activity_logs.php', 'activity logs actions audit history', 'Super Admin');
    topbar_add_search_item($topbarSearchItems, 'User Logs', BASE_URL . 'superadmin/pages/user_logs.php', 'login logout sign in sign out session', 'Super Admin');
    topbar_add_search_item($topbarSearchItems, 'File Logs', BASE_URL . 'superadmin/pages/file_logs.php', 'files uploads exports backup archive delete', 'Super Admin');
    topbar_add_search_item($topbarSearchItems, 'System Backup', BASE_URL . 'superadmin/pages/system_backup.php', 'backup database full differential incremental dump sql', 'Super Admin');
}

if (role_has("ADMIN")) {
    topbar_add_search_item($topbarSearchItems, 'Dashboard', BASE_URL . 'admin/dashboard/main_admin.php', 'dashboard home overview', 'Admin');
    topbar_add_search_item($topbarSearchItems, 'Staff Information', BASE_URL . 'superadmin/pages/user_information.php', 'users staff access employment status', 'Admin');
    topbar_add_search_item($topbarSearchItems, 'Activity Logs', BASE_URL . 'admin/modules/logs/activity_logs.php', 'activity logs audit history', 'Admin');
    topbar_add_search_item($topbarSearchItems, 'Consumable Inventory', BASE_URL . 'admin/modules/consumable/csm_manage_inventory.php', 'csm consumable inventory items', 'Consumable');
    topbar_add_search_item($topbarSearchItems, 'Consumable Categories', BASE_URL . 'admin/modules/consumable/csm_category.php', 'csm category item category', 'Consumable');
    topbar_add_search_item($topbarSearchItems, 'Add Consumable Item', BASE_URL . 'admin/modules/consumable/csm_available_items.php', 'csm add new item', 'Consumable');
    topbar_add_search_item($topbarSearchItems, 'CSM QR Code', BASE_URL . 'admin/modules/consumable/csm_qrcode.php', 'csm qr code', 'Consumable');
    topbar_add_search_item($topbarSearchItems, 'CSM Physical Checking', BASE_URL . 'admin/modules/consumable/csm_physical_checking.php', 'csm physical checking audit', 'Consumable');
    topbar_add_search_item($topbarSearchItems, 'Non-Consumable Inventory', BASE_URL . 'admin/modules/nonconsumable/ast_inventory.php', 'ast property inventory assets', 'Non-Consumable');
    topbar_add_search_item($topbarSearchItems, 'Non-Consumable Categories', BASE_URL . 'admin/modules/nonconsumable/ast_category.php', 'ast category item category', 'Non-Consumable');
    topbar_add_search_item($topbarSearchItems, 'Property Report', BASE_URL . 'admin/modules/nonconsumable/ast_summary_report.php', 'property report summary', 'Non-Consumable');
    topbar_add_search_item($topbarSearchItems, 'Add Property Item', BASE_URL . 'admin/modules/nonconsumable/ast_manage_inventory.php', 'ast add new item', 'Non-Consumable');
    topbar_add_search_item($topbarSearchItems, 'AST QR Code', BASE_URL . 'admin/modules/nonconsumable/ast_qrcode.php', 'ast qr code', 'Non-Consumable');
    topbar_add_search_item($topbarSearchItems, 'AST Physical Checking', BASE_URL . 'admin/modules/nonconsumable/ast_physical_checking.php', 'ast physical checking audit', 'Non-Consumable');
    topbar_add_search_item($topbarSearchItems, 'Issuance', BASE_URL . 'admin/modules/nonconsumable/ast_issuance.php', 'ast issuance release', 'Non-Consumable');
    topbar_add_search_item($topbarSearchItems, 'Property Return', BASE_URL . 'admin/modules/transactions/manage_returns.php', 'return property return', 'Transactions');
    topbar_add_search_item($topbarSearchItems, 'Requisition Item', BASE_URL . 'admin/modules/transactions/requisition.php?type=AST', 'requisition request item ast csm', 'Transactions');
    topbar_add_search_item($topbarSearchItems, 'Facility Inventory Records', BASE_URL . 'admin/modules/transactions/facility_inventory_records.php', 'facility inventory records', 'Transactions');
}

if ((role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) && (user_has_access(array("CSM", "PO", "AST")) || user_has_access("AST") || user_has_access("CSM") || user_has_access("PO"))) {
    topbar_add_search_item($topbarSearchItems, 'Dashboard', BASE_URL . 'admin/dashboard/main_admin_staff.php', 'dashboard home overview', 'Staff');
    if (user_has_access(array("CSM", "PO")) || user_has_access("CSM")) {
        topbar_add_search_item($topbarSearchItems, 'Consumable Inventory', BASE_URL . 'admin/modules/consumable/csm_manage_inventory.php', 'csm consumable inventory items', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'Consumable Categories', BASE_URL . 'admin/modules/consumable/csm_category.php', 'csm category item category', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'Add Consumable Item', BASE_URL . 'admin/modules/consumable/csm_available_items.php', 'csm add new item', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'CSM QR Code', BASE_URL . 'admin/modules/consumable/csm_qrcode.php', 'csm qr code', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'CSM Physical Checking', BASE_URL . 'admin/modules/consumable/csm_physical_checking.php', 'csm physical checking', 'Staff');
    }
    if (user_has_access(array("AST", "PO")) || user_has_access("AST")) {
        topbar_add_search_item($topbarSearchItems, 'Non-Consumable Inventory', BASE_URL . 'admin/modules/nonconsumable/ast_inventory.php', 'ast property inventory assets', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'Non-Consumable Categories', BASE_URL . 'admin/modules/nonconsumable/ast_category.php', 'ast category item category', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'Property Report', BASE_URL . 'admin/modules/nonconsumable/ast_summary_report.php', 'property report summary', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'Add Property Item', BASE_URL . 'admin/modules/nonconsumable/ast_manage_inventory.php', 'ast add new item', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'AST QR Code', BASE_URL . 'admin/modules/nonconsumable/ast_qrcode.php', 'ast qr code', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'AST Physical Checking', BASE_URL . 'admin/modules/nonconsumable/ast_physical_checking.php', 'ast physical checking', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'Issuance', BASE_URL . 'admin/modules/nonconsumable/ast_issuance.php', 'ast issuance release', 'Staff');
        topbar_add_search_item($topbarSearchItems, 'Property Return', BASE_URL . 'admin/modules/transactions/manage_returns.php', 'return property return', 'Transactions');
        topbar_add_search_item($topbarSearchItems, 'Requisition Item', BASE_URL . 'admin/modules/transactions/requisition.php?type=' . (user_has_access('CSM') && !user_has_access('AST') ? 'CSM' : 'AST'), 'requisition request item ast csm', 'Transactions');
        topbar_add_search_item($topbarSearchItems, 'Facility Inventory Records', BASE_URL . 'admin/modules/transactions/facility_inventory_records.php', 'facility inventory records', 'Transactions');
    }
}

if (role_has("USER") || role_has("USERS")) {
    topbar_add_search_item($topbarSearchItems, 'Dashboard', BASE_URL . 'users/dashboard/main_users.php', 'dashboard home overview', 'Users');
    topbar_add_search_item($topbarSearchItems, 'Request Items', BASE_URL . 'users/modules/request_items.php', 'request requisition items', 'Users');
    topbar_add_search_item($topbarSearchItems, 'Facility Records', BASE_URL . 'users/modules/facility_records.php', 'facility records', 'Users');
    topbar_add_search_item($topbarSearchItems, 'Personal Inventory', BASE_URL . 'users/modules/personal_records.php', 'personal inventory records', 'Users');
}

$topbarSearchItems = array_values(array_filter($topbarSearchItems, function($item) {
    return !empty($item['title']) && !empty($item['url']);
}));
?>

<div class="main-header">
    <div class="main-header-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="<?php echo BASE_URL . 'index.php'; ?>" class="logo">
                <img src="<?php echo DISPLAY_LOGO; ?>" alt="navbar brand" class="navbar-brand" height="100%" />
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
        <!-- End Logo Header -->
    </div>

    <!-- Navbar Header -->
    <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
        <div class="container-fluid">
            <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                <form class="position-relative" id="topbarSearchForm" autocomplete="off" style="min-width: 320px; max-width: 460px; width: 100%;">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <button type="submit" class="btn btn-search pe-1">
                                <i class="fa fa-search search-icon"></i>
                            </button>
                        </div>
                        <input type="search" id="topbarSearchInput" placeholder="Search modules, pages, or shortcuts..." class="form-control" />
                    </div>
                    <div id="topbarSearchResults" class="dropdown-menu shadow-sm p-0 w-100 d-none" style="max-height: 320px; overflow-y: auto; position: absolute; inset-inline-start: 0; inset-block-start: calc(100% + 6px); z-index: 1085;"></div>
                </form>
                <!-- date time -->
                <div class="mx-3">
                    <span class="op-7" id="now"></span>
                </div>
            </nav>

            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <!-- search bar [remove if not needed] -->
                <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false" aria-haspopup="true">
                        <i class="fa fa-search"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-search animated fadeIn">
                        <li>
                            <form class="navbar-left navbar-form nav-search p-2" id="topbarMobileSearchForm" autocomplete="off">
                                <div class="input-group">
                                    <input type="search" id="topbarMobileSearchInput" placeholder="Search modules, pages, or shortcuts..." class="form-control" />
                                    <button type="submit" class="btn btn-search pe-1">
                                        <i class="fa fa-search search-icon"></i>
                                    </button>
                                </div>
                                <div id="topbarMobileSearchResults" class="dropdown-menu shadow-sm p-0 w-100 d-none mt-2" style="max-height: 280px; overflow-y: auto; position: static;"></div>
                            </form>
                        </li>
                    </ul>
                </li>

                <!-- messages [remove if not needed] -->
                <li class="nav-item topbar-icon dropdown hidden-caret d-none">
                    <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-envelope"></i>
                    </a>
                    <ul class="dropdown-menu messages-notif-box animated fadeIn" aria-labelledby="messageDropdown">
                        <li>
                            <div
                                class="dropdown-title d-flex justify-content-between align-items-center">
                                Messages
                                <a href="#" class="small">Mark all as read</a>
                            </div>
                        </li>
                        <li>
                            <div class="message-notif-scroll scrollbar-outer">
                                <div class="notif-center">
                                    <a href="#">
                                        <div class="notif-img">
                                            <img src="<?php echo IMG_PATH . $g_photo; ?>" alt="Img Profile" />
                                        </div>
                                        <div class="notif-content">
                                            <span class="subject">Jimmy Denis</span>
                                            <span class="block"> How are you ? </span>
                                            <span class="time">5 minutes ago</span>
                                        </div>
                                    </a>
                                    <a href="#">
                                        <div class="notif-img">
                                            <img src="<?php echo IMG_PATH . $g_photo; ?>" alt="Img Profile" />
                                        </div>
                                        <div class="notif-content">
                                            <span class="subject">Chad</span>
                                            <span class="block"> Ok, Thanks ! </span>
                                            <span class="time">12 minutes ago</span>
                                        </div>
                                    </a>
                                    <a href="#">
                                        <div class="notif-img">
                                            <img src="<?php echo IMG_PATH . $g_photo; ?>" alt="Img Profile" />
                                        </div>
                                        <div class="notif-content">
                                            <span class="subject">Jhon Doe</span>
                                            <span class="block">
                                                Ready for the meeting today...
                                            </span>
                                            <span class="time">12 minutes ago</span>
                                        </div>
                                    </a>
                                    <a href="#">
                                        <div class="notif-img">
                                            <img src="<?php echo IMG_PATH . $g_photo; ?>" alt="Img Profile" />
                                        </div>
                                        <div class="notif-content">
                                            <span class="subject">Talha</span>
                                            <span class="block"> Hi, Apa Kabar ? </span>
                                            <span class="time">17 minutes ago</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </li>
                        <li>
                            <a class="see-all" href="javascript:void(0);">See all messages<i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- notification [remove if not needed] -->
                <li class="nav-item topbar-icon dropdown hidden-caret">
                    <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-bell"></i>
                        <?php if ($topbarNotifTotal > 0) { ?>
                            <span class="notification"><?php echo $topbarNotifTotal; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                        <li>
                            <div class="dropdown-title">
                                <?php echo $topbarNotifTotal > 0 ? ('You have ' . $topbarNotifTotal . ' notification(s)') : 'No new notifications'; ?>
                            </div>
                        </li>
                        <li>
                            <div class="notif-scroll scrollbar-outer">
                                <div class="notif-center">
                                    <?php if ($topbarNotifTotal > 0): ?>
                                        <?php foreach ($topbarNotifications as $notif): ?>
                                            <a href="<?php echo htmlspecialchars($notif['url']); ?>">
                                                <div class="notif-icon <?php echo htmlspecialchars($notif['icon_class']); ?>">
                                                    <i class="<?php echo htmlspecialchars($notif['icon']); ?>"></i>
                                                </div>
                                                <div class="notif-content">
                                                    <span class="block"><?php echo htmlspecialchars($notif['text']); ?></span>
                                                    <span class="time">Live</span>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="px-3 py-2 text-muted small">You're all caught up.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>

                <!-- quick actions [remove if not needed] -->
                <li class="nav-item topbar-icon dropdown hidden-caret d-none">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <i class="fas fa-layer-group"></i>
                    </a>
                    <div class="dropdown-menu quick-actions animated fadeIn">
                        <div class="quick-actions-header">
                            <span class="title mb-1">Quick Actions</span>
                            <span class="subtitle op-7">Shortcuts</span>
                        </div>
                        <div class="quick-actions-scroll scrollbar-outer">
                            <div class="quick-actions-items">
                                <div class="row m-0">
                                    <a class="col-6 col-md-4 p-0" href="#">
                                        <div class="quick-actions-item">
                                            <div class="avatar-item bg-danger rounded-circle">
                                                <i class="far fa-calendar-alt"></i>
                                            </div>
                                            <span class="text">Calendar</span>
                                        </div>
                                    </a>
                                    <a class="col-6 col-md-4 p-0" href="#">
                                        <div class="quick-actions-item">
                                            <div
                                                class="avatar-item bg-warning rounded-circle">
                                                <i class="fas fa-map"></i>
                                            </div>
                                            <span class="text">Maps</span>
                                        </div>
                                    </a>
                                    <a class="col-6 col-md-4 p-0" href="#">
                                        <div class="quick-actions-item">
                                            <div class="avatar-item bg-info rounded-circle">
                                                <i class="fas fa-file-excel"></i>
                                            </div>
                                            <span class="text">Reports</span>
                                        </div>
                                    </a>
                                    <a class="col-6 col-md-4 p-0" href="#">
                                        <div class="quick-actions-item">
                                            <div
                                                class="avatar-item bg-success rounded-circle">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <span class="text">Emails</span>
                                        </div>
                                    </a>
                                    <a class="col-6 col-md-4 p-0" href="#">
                                        <div class="quick-actions-item">
                                            <div
                                                class="avatar-item bg-primary rounded-circle">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </div>
                                            <span class="text">Invoice</span>
                                        </div>
                                    </a>
                                    <a class="col-6 col-md-4 p-0" href="#">
                                        <div class="quick-actions-item">
                                            <div
                                                class="avatar-item bg-secondary rounded-circle">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                            <span class="text">Payments</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- profile -->
                <li class="nav-item topbar-user dropdown hidden-caret">
                    <!-- image, name, position -->
                    <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <div class="avatar-sm">
                            <img src="<?php echo IMG_PATH . $g_photo; ?>" alt="..." class="avatar-img rounded-circle">
                        </div>
                        <span class="profile-username">
                            <span class="fw-bold"><?php echo $g_fullname; ?></span><br>
                            <span class="op-7"><?php echo $g_position; ?></span>
                        </span>
                    </a>
                    <!-- image, name, position, profile btn, sign out btn -->
                    <ul class="dropdown-menu dropdown-user animated fadeIn">
                        <div class="dropdown-user-scroll scrollbar-outer">
                            <li>
                                <div class="user-box">
                                    <div class="avatar-lg">
                                        <img src="<?php echo IMG_PATH . $g_photo; ?>" alt="image profile" class="avatar-img rounded">
                                    </div>
                                    <div class="u-text">
                                        <h4><?php echo $g_fullname; ?></h4>
                                        <p class="text-muted"><?php echo $g_position; ?></p>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">My Profile</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>sign-out.php">Sign out</a>
                            </li>
                        </div>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <!-- End Navbar -->
</div>

<script>
(function() {
    const searchItems = <?php echo json_encode($topbarSearchItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function findMatches(query) {
        const q = normalize(query);
        if (!q) {
            return [];
        }
        return searchItems.map(function(item) {
            const haystack = normalize([item.title, item.keywords, item.section].join(' '));
            let score = 0;
            if (normalize(item.title) === q) {
                score += 100;
            }
            if (normalize(item.title).startsWith(q)) {
                score += 80;
            }
            if (normalize(item.section).startsWith(q)) {
                score += 20;
            }
            if (normalize(item.keywords).indexOf(q) !== -1) {
                score += 40;
            }
            if (score === 0 && haystack.indexOf(q) === -1) {
                return null;
            }
            return Object.assign({ score: score }, item);
        }).filter(function(item) {
            return item !== null;
        }).sort(function(a, b) {
            if (b.score !== a.score) return b.score - a.score;
            return String(a.title).localeCompare(String(b.title));
        }).slice(0, 8);
    }

    function renderResults(container, matches) {
        if (!container) return;
        container.innerHTML = '';

        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'dropdown-item-text text-muted small py-2 px-3';
            empty.textContent = 'No modules found';
            container.appendChild(empty);
            container.classList.remove('d-none');
            return;
        }

        matches.forEach(function(item) {
            const link = document.createElement('a');
            link.href = item.url;
            link.className = 'dropdown-item py-2';
            link.innerHTML = '<div class="fw-semibold">' + item.title + '</div>' +
                '<div class="small text-muted">' + (item.section || 'Module') + '</div>';
            container.appendChild(link);
        });

        container.classList.remove('d-none');
    }

    function hideResults(container) {
        if (!container) return;
        container.classList.add('d-none');
    }

    function bindSearch(formId, inputId, resultsId) {
        const form = document.getElementById(formId);
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        if (!form || !input || !results) return;

        input.addEventListener('input', function() {
            renderResults(results, findMatches(input.value));
        });

        input.addEventListener('focus', function() {
            const matches = findMatches(input.value);
            if (matches.length) {
                renderResults(results, matches);
            }
        });

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const matches = findMatches(input.value);
            if (matches.length) {
                window.location.href = matches[0].url;
            }
        });

        document.addEventListener('click', function(event) {
            if (!form.contains(event.target)) {
                hideResults(results);
            }
        });

        results.addEventListener('click', function(event) {
            const target = event.target.closest('a');
            if (target) {
                hideResults(results);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindSearch('topbarSearchForm', 'topbarSearchInput', 'topbarSearchResults');
        bindSearch('topbarMobileSearchForm', 'topbarMobileSearchInput', 'topbarMobileSearchResults');
    });
})();
</script>
