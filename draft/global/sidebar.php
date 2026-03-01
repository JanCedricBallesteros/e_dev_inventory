<?php $activePage = ACTIVE_PAGE; //for active page (e.g. "index") 

if (!isset($g_user_role)) {
    include DOMAIN_PATH . "/404.php";
    exit();
}
?>
<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-heading">Navigation</li>

        <?php if ($g_user_role == 'ADMIN') { ?> <!-- admin navigation -->
            <li class="nav-item">
                <a class="nav-link <?php echo $activePage == 'main_admin' ? '' : 'collapsed'; ?>" href="<?php echo BASE_URL; ?>admin/main_admin.php">
                    <i class="bi bi-people-fill"></i>
                    <span>User Information</span>
                </a>
            </li>


        <?php } elseif ($g_user_role == 'REGISTRAR') { ?>

        
        <?php } ?>
    </ul>
</aside><!-- End Sidebar-->