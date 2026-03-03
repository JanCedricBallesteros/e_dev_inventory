<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(role_has("SUPER_ADMIN") || role_has("ADMIN"))) {
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

$kpis = array(
    array('label' => 'AST Items', 'value' => dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory"), 'icon' => 'bi-box-seam'),
    array('label' => 'AST Categories', 'value' => dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory_category"), 'icon' => 'bi-tags'),
    array('label' => 'CSM Items', 'value' => dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory"), 'icon' => 'bi-boxes'),
    array('label' => 'CSM Categories', 'value' => dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory_category"), 'icon' => 'bi-grid-3x3-gap'),
    array('label' => 'Pending Requisitions', 'value' => dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='pending'"), 'icon' => 'bi-hourglass-split'),
    array('label' => 'Activity Logs', 'value' => dashboard_count("SELECT COUNT(*) AS cnt FROM activity_log"), 'icon' => 'bi-activity'),
);

$quick_actions = array(
    array('label' => 'AST Inventory', 'href' => BASE_URL . 'admin/modules/nonconsumable/ast_inventory.php', 'icon' => 'bi-table'),
    array('label' => 'AST Add New Item', 'href' => BASE_URL . 'admin/modules/nonconsumable/ast_manage_inventory.php', 'icon' => 'bi-plus-square'),
    array('label' => 'CSM Inventory', 'href' => BASE_URL . 'admin/modules/consumable/csm_manage_inventory.php', 'icon' => 'bi-box'),
    array('label' => 'Requisition', 'href' => BASE_URL . 'admin/modules/transactions/requisition.php?type=AST', 'icon' => 'bi-list-check'),
    array('label' => 'Activity Logs', 'href' => BASE_URL . 'admin/modules/logs/activity_logs.php', 'icon' => 'bi-journal-text'),
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
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; }
        .kpi-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; background: #fff; }
        .kpi-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; }
        .kpi-value { font-size: 22px; font-weight: 700; }
        .kpi-icon { font-size: 20px; color: #0d6efd; }
        .section-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; }
        .quick-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; background: #fff; display: flex; gap: 10px; align-items: center; text-decoration: none; }
        .quick-card i { font-size: 20px; color: #198754; }
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
        <h1 class="h4 fw-semibold mb-1">Admin Dashboard</h1>
        <p class="text-muted small mb-0">Overview of AST/CSM inventory and transactions.</p>
    </div>

    <section class="section">
        <div class="row g-3">
            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold">
                        <i class="bi bi-graph-up-arrow"></i>&ensp;Key Metrics
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="kpi-grid">
                            <?php foreach ($kpis as $kpi) { ?>
                                <div class="kpi-card">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="kpi-label"><?php echo html($kpi['label']); ?></div>
                                        <i class="bi <?php echo html($kpi['icon']); ?> kpi-icon"></i>
                                    </div>
                                    <div class="kpi-value"><?php echo (int)$kpi['value']; ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold">
                        <i class="bi bi-lightning-charge-fill"></i>&ensp;Quick Actions
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <div class="quick-grid">
                            <?php foreach ($quick_actions as $action) { ?>
                                <a class="quick-card" href="<?php echo html($action['href']); ?>">
                                    <i class="bi <?php echo html($action['icon']); ?>"></i>
                                    <div>
                                        <div class="fw-semibold text-dark"><?php echo html($action['label']); ?></div>
                                        <div class="small muted">Open module</div>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
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
