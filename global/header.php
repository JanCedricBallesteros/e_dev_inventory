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
                <!-- search bar [remove if not needed] -->
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button type="submit" class="btn btn-search pe-1">
                            <i class="fa fa-search search-icon"></i>
                        </button>
                    </div>
                    <input type="text" placeholder="Search ..." class="form-control" />
                </div>
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
                        <form class="navbar-left navbar-form nav-search">
                            <div class="input-group">
                                <input type="text" placeholder="Search ..." class="form-control" />
                            </div>
                        </form>
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
