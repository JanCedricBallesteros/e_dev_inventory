<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(
    role_has("ADMIN") ||
    (
        (role_has("ADMIN_STAFF") || role_has("ADMINSTAFF")) &&
        user_has_access(array("CSM", "PO"))
    )
)) {
    header("Location: " . BASE_URL);
    exit();
}

// Your page logic here
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-header bg-eclearance text-white fw-semibold">
                    <i class="bi bi-clipboard-check"></i>&ensp;Physical Checking of Consumables
                </div>
                <div class="card-body mt-3 bg-white">
                    <!-- Your content here dito mo lagay ha, LABYU -->
                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
</html>
