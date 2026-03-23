<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(role_has("USER") || role_has("USERS"))) {
    header("Location: " . BASE_URL);
    exit();
}

$display_name = !empty($g_fullname) ? $g_fullname : (!empty($g_name) ? $g_name : 'User');
$display_position = !empty($g_position) ? $g_position : 'User';
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .muted { color: #6c757d; }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 class="h4 fw-semibold mb-1">User Dashboard</h1>
            <p class="text-muted small mb-0" id="greeting-text"></p>
        </div>
        <script>
        (function() {
            var name = <?php echo json_encode($display_name); ?>;
            var hour = new Date().getHours();
            var greeting = hour < 12 ? 'Good morning' : (hour < 18 ? 'Good afternoon' : 'Good evening');
            document.getElementById('greeting-text').textContent = greeting + ', ' + name + '.';
        })();
        </script>

        <section class="section">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card section-card">
                        <div class="card-header bg-eclearance text-white fw-semibold">
                            <i class="bi bi-person-badge-fill"></i>&ensp;Profile Snapshot
                        </div>
                        <div class="card-body mt-3 bg-white">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="small text-muted">Name</div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($display_name); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted">Position</div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($display_position); ?></div>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">This dashboard will expand as request and inventory features go live.</div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card section-card">
                        <div class="card-header bg-eclearance text-white fw-semibold">
                            <i class="bi bi-lightning-charge-fill"></i>&ensp;Quick Actions
                        </div>
                        <div class="card-body mt-3 bg-white">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <a class="btn btn-outline-primary w-100" href="<?php echo BASE_URL; ?>users/modules/request_items.php">Request Items</a>
                                </div>
                                <div class="col-md-4">
                                    <a class="btn btn-outline-secondary w-100" href="<?php echo BASE_URL; ?>users/modules/facility_records.php">Facility Records</a>
                                </div>
                                <div class="col-md-4">
                                    <a class="btn btn-outline-secondary w-100" href="<?php echo BASE_URL; ?>users/modules/personal_records.php">Personal Inventory</a>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">Buttons link to user pages (placeholders for now).</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
</html>




