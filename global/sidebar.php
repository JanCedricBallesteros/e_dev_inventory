<?php

/** 
==================================================================
 File name   : sidebar.php
 Version     : 1.0.0
 Begin       : 2026-02-26
 Last Update : 
 Author      : 
 Description : sidebar (FOR ADMINS UI).
 =================================================================
 **/

## activation on each pages
// function navigation_active($page, $class = 'active')
// {
//     global $active_page;

//     $pageArray = array_map('trim', explode(',', $page));
//     return in_array($active_page, $pageArray) ? $class : '';
// }

function navigation_active($pages, $class = 'active', $conditions = [])
{
    global $active_page;

    $pageArray = array_map('trim', explode(',', $pages));

    // Check if current page matches
    if (!in_array($active_page, $pageArray)) {
        return '';
    }

    // If no GET conditions required
    if (empty($conditions)) {
        return $class;
    }

    // Check GET conditions
    foreach ($conditions as $key => $values) {
        if (!isset($_GET[$key])) {
            return '';
        }
        $values = (array) $values; // ensure array
        if (!in_array($_GET[$key], $values)) {
            return '';
        }
    }

    return $class;
}
?>
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
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
        <!-- End Logo Header -->
    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <?php if ($g_user_role == 'SUPER_ADMIN') { ?>
                    <!-- navigation section (remove if not needed) -->
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section"><?php echo ACCESS_NAME[$g_user_role]; ?></h4> <!-- change based on role -->
                    </li>

                    <!-- start navigation -->

                    <li class="nav-item <?php echo navigation_active("main_admin"); ?>">
                        <a href="<?php echo BASE_URL . "admin/main_admin.php" ?>">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("user_information"); ?>">
                        <a href="<?php echo BASE_URL . "admin/user_information.php" ?>">
                            <i class="fas fa-users"></i>
                            <p>User Information</p>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("activity_log", "active submenu", ["user" => ["super_admin", "admin"]]); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#activity_log_nav">
                            <i class="fas fa-list"></i>
                            <p>Activity Log</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("activity_log", "show", ["user" => ["super_admin", "admin"]]); ?>" id="activity_log_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("activity_log", "active", ["user" => ["super_admin"]]); ?>">
                                    <a href="<?php echo BASE_URL . "admin/activity_log.php?user=super_admin" ?>">
                                        <span class="sub-item">Super Administrator</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("activity_log", "active", ["user" => ["admin"]]); ?>">
                                    <a href="<?php echo BASE_URL . "admin/activity_log.php?user=admin"; ?>">
                                        <span class="sub-item">Administrator</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item <?php echo navigation_active("user_log", "active submenu", ["user" => ["super_admin", "admin"]]); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#user_log_nav">
                            <i class="fas fa-list"></i>
                            <p>User Log</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("user_log", "show", ["user" => ["super_admin", "admin"]]); ?>" id="user_log_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("user_log", "active", ["user" => ["super_admin"]]); ?>">
                                    <a href="<?php echo BASE_URL . "admin/user_log.php?user=super_admin" ?>">
                                        <span class="sub-item">Super Administrator</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("user_log", "active", ["user" => ["admin"]]); ?>">
                                    <a href="<?php echo BASE_URL . "admin/user_log.php?user=admin"; ?>">
                                        <span class="sub-item">Administrator</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>


                    <!-- for sub menu, change href and id (it should be unique id) -->
                    <li class="nav-item <?php echo navigation_active("other,admin", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#sub_nav">
                            <i class="fas fa-layer-group"></i>
                            <p>Parent Nav</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("other,admin", "show"); ?>" id="sub_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("other"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/other.php" ?>">
                                        <span class="sub-item">Sub Parent Nav 1</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("admin"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/admin.php" ?>">
                                        <span class="sub-item">Sub Parent Nav 2</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>


                    <li class="nav-item <?php echo navigation_active("other"); ?>">
                        <a href="<?php echo BASE_URL . "admin/other.php" ?>">
                            <i class="fas fa-desktop"></i>
                            <p>With bagdes Nav</p>
                            <span class="badge badge-success">4</span>
                        </a>
                    </li>


                    <li class="nav-item <?php echo navigation_active("other"); ?>">
                        <a href="<?php echo BASE_URL . "admin/other.php" ?>">
                            <i class="fas fa-file"></i>
                            <p>Single Nav</p>
                        </a>
                    </li>

                <?php } else if ($g_user_role == 'ADMIN') { ?>
                    <!-- navigation section (remove if not needed) -->
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section"><?php echo ACCESS_NAME[$g_user_role]; ?></h4> <!-- change based on role -->
                    </li>

                    <!-- start navigation -->

                    <!-- for sub menu, change href and id (it should be unique id) -->
                    <li class="nav-item <?php echo navigation_active("main_admin,admin", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#sub_nav">
                            <i class="fas fa-layer-group"></i>
                            <p>Parent Nav</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("main_admin,admin", "show"); ?>" id="sub_nav">
                            <ul class="nav nav-collapse">
                                <li class="<?php echo navigation_active("main_admin"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/main_admin.php" ?>">
                                        <span class="sub-item">Sub Parent Nav 1</span>
                                    </a>
                                </li>
                                <li class="<?php echo navigation_active("admin"); ?>">
                                    <a href="<?php echo BASE_URL . "admin/admin.php" ?>">
                                        <span class="sub-item">Sub Parent Nav 2</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>


                    <li class="nav-item <?php echo navigation_active("other"); ?>">
                        <a href="<?php echo BASE_URL . "admin/other.php" ?>">
                            <i class="fas fa-desktop"></i>
                            <p>With bagdes Nav</p>
                            <span class="badge badge-success">4</span>
                        </a>
                    </li>


                    <li class="nav-item <?php echo navigation_active("other"); ?>">
                        <a href="<?php echo BASE_URL . "admin/other.php" ?>">
                            <i class="fas fa-file"></i>
                            <p>Single Nav</p>
                        </a>
                    </li>

                <?php } ?>
            </ul>
        </div>
    </div>
</div>