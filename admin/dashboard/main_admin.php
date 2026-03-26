<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!role_has("ADMIN")) {
    header("Location: " . BASE_URL);
    exit();
}

function dashboard_count($sql, $field = 'cnt')
{
    $res = call_mysql_query($sql);
    if ($res && ($row = call_mysql_fetch_array($res))) {
        return (int)($row[$field] ?? 0);
    }
    return 0;
}

$ast_items = dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory");
$ast_categories = dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory_category");
$csm_items = dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory");
$csm_categories = dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory_category");
$pending_reqs = dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='pending'");
$activity_logs = dashboard_count("SELECT COUNT(*) AS cnt FROM activity_log");

$ast_cards = array(
    array('label' => 'AST Items', 'value' => $ast_items, 'href' => BASE_URL . 'admin/modules/nonconsumable/ast_inventory.php', 'icon' => 'bi-box-seam'),
    array('label' => 'AST Categories', 'value' => $ast_categories, 'href' => BASE_URL . 'admin/modules/nonconsumable/ast_category.php', 'icon' => 'bi-tags'),
    array('label' => 'Add AST Item', 'value' => '', 'href' => BASE_URL . 'admin/modules/nonconsumable/ast_manage_inventory.php', 'icon' => 'bi-plus-square'),
);

$csm_cards = array(
    array('label' => 'CSM Items', 'value' => $csm_items, 'href' => BASE_URL . 'admin/modules/consumable/csm_manage_inventory.php', 'icon' => 'bi-boxes'),
    array('label' => 'CSM Categories', 'value' => $csm_categories, 'href' => BASE_URL . 'admin/modules/consumable/csm_category.php', 'icon' => 'bi-grid-3x3-gap'),
    array('label' => 'CSM QR Codes', 'value' => '', 'href' => BASE_URL . 'admin/modules/consumable/csm_qrcode.php', 'icon' => 'bi-qr-code'),
);

$ops_cards = array(
    array('label' => 'Pending Requisitions', 'value' => $pending_reqs, 'href' => BASE_URL . 'admin/modules/transactions/requisition.php?type=AST', 'icon' => 'bi-hourglass-split'),
    array('label' => 'Activity Logs', 'value' => $activity_logs, 'href' => BASE_URL . 'admin/modules/logs/activity_logs.php', 'icon' => 'bi-activity'),
);
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        :root{
            --dash-ink: #1f2937;
            --dash-muted: #6c757d;
            --dash-border: #e5e7eb;
            --dash-card: #ffffff;
            --dash-accent: #0d6efd;
            --dash-accent-soft: #eef5ff;
        }
        .dashboard-wrap{ position: relative; }
        .section-card { border: 1px solid var(--dash-border); border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .section-card .card-header{ background: var(--bg-eclearance-rgb); color: #fff; border-radius: 12px 12px 0 0; }
        .dash-header{
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            padding: 16px 18px;
            background: #fff;
        }
        .dash-header h1{ color: var(--dash-ink); font-weight: 700; }
        .dash-header p{ color: var(--dash-muted); }
        .pill{ background: var(--dash-accent-soft); color: var(--dash-accent); font-size: 12px; font-weight: 600; border-radius: 999px; padding: 4px 10px; }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .action-card { border: 1px solid var(--dash-border); border-radius: 12px; padding: 12px 14px; background: #fff; display: flex; gap: 12px; align-items: center; text-decoration: none; transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .action-card:hover{ transform: translateY(-2px); box-shadow: 0 8px 14px rgba(0,0,0,0.08); }
        .action-icon { width: 42px; height: 42px; border-radius: 10px; background: var(--dash-accent-soft); color: var(--dash-accent); display:flex; align-items:center; justify-content:center; font-size: 18px; }
        .action-label { font-size: 12px; color: var(--dash-muted); text-transform: uppercase; letter-spacing: .35px; }
        .action-value { font-size: 20px; font-weight: 700; color: var(--dash-ink); line-height: 1.2; }
        .action-sub { font-size: 12px; color: var(--dash-muted); }
        .muted { color: var(--dash-muted); }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="dashboard-wrap">
        <div class="dash-header mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Admin Dashboard</h1>
                    <p class="small mb-0">Monitor inventory, categories, requisitions, and activity in one view.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="pill">AST: <?php echo (int)$ast_items; ?> items</span>
                    <span class="pill">CSM: <?php echo (int)$csm_items; ?> items</span>
                </div>
            </div>
        </div>

        <section class="section">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="card section-card">
                        <div class="card-header fw-semibold">
                            <i class="bi bi-box-seam"></i>&ensp;AST Actions & Stats
                        </div>
                        <div class="card-body bg-white">
                            <div class="action-grid">
                                <?php foreach ($ast_cards as $action) { ?>
                                    <a class="action-card" href="<?php echo html($action['href']); ?>">
                                        <div class="action-icon"><i class="bi <?php echo html($action['icon']); ?>"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="action-label"><?php echo html($action['label']); ?></div>
                                            <?php if ($action['value'] !== '') { ?>
                                                <div class="action-value"><?php echo (int)$action['value']; ?></div>
                                            <?php } else { ?>
                                                <div class="action-sub">Open module</div>
                                            <?php } ?>
                                        </div>
                                        <i class="bi bi-arrow-right-short text-muted fs-4"></i>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card section-card">
                        <div class="card-header fw-semibold">
                            <i class="bi bi-boxes"></i>&ensp;CSM Actions & Stats
                        </div>
                        <div class="card-body bg-white">
                            <div class="action-grid">
                                <?php foreach ($csm_cards as $action) { ?>
                                    <a class="action-card" href="<?php echo html($action['href']); ?>">
                                        <div class="action-icon"><i class="bi <?php echo html($action['icon']); ?>"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="action-label"><?php echo html($action['label']); ?></div>
                                            <?php if ($action['value'] !== '') { ?>
                                                <div class="action-value"><?php echo (int)$action['value']; ?></div>
                                            <?php } else { ?>
                                                <div class="action-sub">Open module</div>
                                            <?php } ?>
                                        </div>
                                        <i class="bi bi-arrow-right-short text-muted fs-4"></i>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card section-card">
                        <div class="card-header fw-semibold">
                            <i class="bi bi-gear"></i>&ensp;Admin Operations
                        </div>
                        <div class="card-body bg-white">
                            <div class="action-grid">
                                <?php foreach ($ops_cards as $action) { ?>
                                    <a class="action-card" href="<?php echo html($action['href']); ?>">
                                        <div class="action-icon"><i class="bi <?php echo html($action['icon']); ?>"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="action-label"><?php echo html($action['label']); ?></div>
                                            <?php if ($action['value'] !== '') { ?>
                                                <div class="action-value"><?php echo (int)$action['value']; ?></div>
                                            <?php } else { ?>
                                                <div class="action-sub">Open module</div>
                                            <?php } ?>
                                        </div>
                                        <i class="bi bi-arrow-right-short text-muted fs-4"></i>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include_once FOOTER_PATH; ?>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
</html>
