<?php
$db_general_id = "";
$db_full_name = "";
$img = "";
$db_birth_date = "";
$db_sex = "";
$db_email = "";
$db_recovery_email = "";
$db_position = "";
$db_username = "";
$db_password = "";
$notif_header = [];

$users_sql = "SELECT * FROM users WHERE general_id = '" . $g_general_id . "' LIMIT 1";
if ($users_query = mysqli_query($db_connect, $users_sql)) {
    if ($users_num_row = mysqli_num_rows($users_query)) {
        while ($users_data = call_mysql_fetch_array($users_query)) {
            $users_data = array_html($users_data);
            $db_general_id = $users_data['general_id'];
            $img = $users_data['img'];
            $db_full_name = $users_data['f_name'] . " " . ($users_data['m_name'] != '' ? $users_data['m_name'] . ' ' : '') . $users_data['l_name'] . " " . $users_data['suffix'];
            $db_birth_date = date('M j, Y', strtotime($users_data['birth_date']));
            $db_sex = $users_data['sex'];
            $db_email = $users_data['email_address'];
            $db_recovery_email = $users_data['recovery_email'];
            $db_position = $users_data['position'];
        }
    }
    mysqli_free_result($users_query);
}


// Fetch login data
$login_sql = "SELECT username, password FROM users WHERE user_id = '$s_user_id' LIMIT 1";
if ($login_query = mysqli_query($db_connect, $login_sql)) {
    if ($login_data = mysqli_fetch_assoc($login_query)) {
        $login_data = array_html($login_data);
        $db_username = $login_data['username'];
        $db_password = $login_data['password'];
    }
    mysqli_free_result($login_query);
}

// // fetch faculty with department information
// if ($_query = call_mysql_query("SELECT f.*, d.department 
//                                 FROM faculty_info f
//                                 JOIN departments d ON f.department = d.department_id
//                                 ORDER BY d.department ASC")) {
//     if ($_num = call_mysql_num_rows($_query)) {
//         while ($_data = call_mysql_fetch_array($_query)) {
//             // Assuming faculty_info has columns like faculty_id, name, etc.
//             $faculty[$_data['faculty_id']] = [
//                 'name' => $_data['name'],
//                 'department' => $_data['department']
//                 // Add other faculty details as needed
//             ];
//         }
//     }
// }

//fetch notif table
$default_query = "SELECT * FROM notification WHERE recipient_id ='" . $g_general_id . "' AND unread = 0 ORDER BY notif_id DESC LIMIT 20";
if ($query = call_mysql_query($default_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($notif = call_mysql_fetch_array($query)) {
            $notif = array_html($notif);
            $created = new DateTime($notif['created_at']);
            $now = new DateTime(DATE_TIME);
            $when1 = $created->diff($now)->days;
            $notif['when'] = $when1;
            $notif_header[] = $notif;
        }
    }
}

$g_profile_path = $g_user_role == "STUDENT" ? "app/student_profile.php" : ($g_user_role == "FACULTY" ? "app/faculty_profile.php" : ($g_user_role == "OFFICIAL" ? "app/official_profile.php" : "admin/user_profile.php"));
?>
<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between top-nav">
        <a href="<?php echo BASE_URL; ?>" class="logo d-flex align-items-center">
            <img src="<?php echo ECLEARANCE_LOGO; ?>" alt="" class="d-none d-md-inline-block">
            <img src="<?php echo ECLEARANCE_DISPLAY; ?>" alt="" class="d-inline-block d-md-none">
        </a>
        <i class="bi bi-list toggle-sidebar-btn" onclick="not_header()"></i>
    </div><!-- End Logo -->

    <!-- datetime -->
    <div class="search-bar align-items-center justify-content-center text-center float-right d-none d-sm-flex" style="font-size:small;">
        <span id="now"></span>
    </div>

    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">

            <!-- <li class="nav-item dropdown">
                <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span id="new_notif_count" class="badge bg-eclearance badge-number"></span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications overflow-auto" style="height: 400px;">
                    <li id="notif_dd_hd" class="dropdown-header">
                        You have 4 new notifications
                        <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <div id="notif_content">

                    </div>
                    <li class="dropdown-footer" style="position:sticky;">
                        <a href="<?php echo BASE_URL ?>app/notification.php">Show all notifications</a>
                    </li>

                </ul>

            </li> -->

            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" id="dropdownMenuClickableInside" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <?php
                    if (isset($img) && !empty($img)) {
                        echo '<img src="' .  BASE_URL . 'view-image.php?file=' . $img . '" class="rounded-circle" style="aspect-ratio: 1 / 1; object-fit: cover;"> <br>';
                    } else {
                        echo '<img src="' .  BASE_URL . 'view-image.php?file=' . 'profile-img.png" class="rounded-circle" style="aspect-ratio: 1 / 1; object-fit: cover;"> <br>';
                    }
                    ?> <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $g_name; ?> <br> <span><?php echo $g_user_role; ?></span></span>

                </a>

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile" aria-labelledby="dropdownMenuClickableInside">
                    <li class="dropdown-header">
                        <h6><?php echo $g_fullname; ?></h6>
                        <span><?php echo $g_position; ?></span>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="<?php echo BASE_URL . $g_profile_path; ?>">
                            <i class="bi bi-person"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="<?php echo BASE_URL; ?>sign-out.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sign Out</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

</header><!-- End Header -->