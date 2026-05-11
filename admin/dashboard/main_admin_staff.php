<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(role_has("ADMIN_STAFF") || role_has("ADMINSTAFF"))) {
    header("Location: " . BASE_URL);
    exit();
}

function staff_dashboard_count($sql, $field = 'cnt')
{
    $res = call_mysql_query($sql);
    if ($res && ($row = call_mysql_fetch_array($res))) {
        return (int)($row[$field] ?? 0);
    }
    return 0;
}

$has_ast = user_has_access(array("AST", "PO"));
$has_csm = user_has_access(array("CSM", "PO"));
$has_any_access = user_has_access(array("PO", "CSM", "AST"));

$kpis = array(
    array('label' => 'Pending Requisitions', 'value' => staff_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='pending'"), 'icon' => 'bi-hourglass-split'),
    array('label' => 'Reviewed Requisitions', 'value' => staff_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='reviewed'"), 'icon' => 'bi-check2-circle'),
    array('label' => 'Approved Requisitions', 'value' => staff_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='approved'"), 'icon' => 'bi-patch-check'),
);
$disapproved_reqs = staff_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='disapproved'");
$facility_active = staff_dashboard_count("SELECT COUNT(*) AS cnt FROM facility_records_assignments WHERE status='ACTIVE'");
$facility_reported = staff_dashboard_count("SELECT COUNT(*) AS cnt FROM facility_records_assignments WHERE status='REPORTED'");
$activity_today = staff_dashboard_count("SELECT COUNT(*) AS cnt FROM activity_log WHERE DATE(date_log) = CURDATE()");
$ast_unavailable = staff_dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory WHERE is_available = 0");
$csm_critical = staff_dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory WHERE current_unit_quantity <= unit_crit_level");

if ($has_ast) {
    $kpis[] = array('label' => 'AST Items', 'value' => staff_dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory"), 'icon' => 'bi-box-seam');
}
if ($has_csm) {
    $kpis[] = array('label' => 'CSM Items', 'value' => staff_dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory"), 'icon' => 'bi-boxes');
}

$quick_actions = array();
if ($has_ast) {
    $quick_actions[] = array('label' => 'AST Inventory', 'href' => BASE_URL . 'admin/modules/nonconsumable/ast_inventory.php', 'icon' => 'bi-table');
    $quick_actions[] = array('label' => 'AST Add New Item', 'href' => BASE_URL . 'admin/modules/nonconsumable/ast_manage_inventory.php', 'icon' => 'bi-plus-square');
}
if ($has_csm) {
    $quick_actions[] = array('label' => 'CSM Inventory', 'href' => BASE_URL . 'admin/modules/consumable/csm_manage_inventory.php', 'icon' => 'bi-box');
    $quick_actions[] = array('label' => 'CSM Issuance', 'href' => BASE_URL . 'admin/modules/consumable/csm_issuance.php', 'icon' => 'bi-truck');
}
if ($has_any_access) {
    $quick_actions[] = array('label' => 'Requisition', 'href' => BASE_URL . 'admin/modules/transactions/requisition.php?type=AST', 'icon' => 'bi-list-check');
    $quick_actions[] = array('label' => 'Activity Logs', 'href' => BASE_URL . 'admin/modules/logs/activity_logs.php', 'icon' => 'bi-journal-text');
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        :root {
            --staff-ink: #12213b;
            --staff-muted: #5f6e82;
            --staff-border: #d9e5f4;
        }
        .staff-hero {
            background: #1460b9;
            border-radius: 15px;
            color: #fff;
            padding: 18px;
            margin-bottom: 12px;
            box-shadow: 0 12px 24px rgba(8, 42, 93, .2);
        }
        .staff-pill {
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.34);
            color: #fff;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
        }
        .kpi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .kpi-card { border: 1px solid var(--staff-border); border-radius: 11px; padding: 13px; background: #fff; box-shadow: 0 6px 14px rgba(9,44,97,.06); }
        .kpi-label { font-size: 11px; color: var(--staff-muted); text-transform: uppercase; letter-spacing: .5px; }
        .kpi-value { font-size: 22px; font-weight: 700; color: var(--staff-ink); }
        .kpi-icon { font-size: 18px; color: #0d6efd; }
        .section-card { border: 1px solid var(--staff-border); border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .section-card .card-header { background: #fff; color: var(--staff-ink); border-bottom: 1px solid var(--staff-border); }
        .quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; }
        .quick-card { border: 1px solid var(--staff-border); border-radius: 10px; padding: 14px; background: #fff; display: flex; gap: 10px; align-items: center; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease; }
        .quick-card:hover { transform: translateY(-2px); box-shadow: 0 10px 16px rgba(0,0,0,.08); }
        .quick-card i { font-size: 20px; color: #12895f; }
        .signal-card { border: 1px solid var(--staff-border); border-radius: 10px; padding: 12px; background: #fff; }
        .signal-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; border-bottom: 1px dashed #e6eef9; padding: 7px 0; }
        .signal-row:last-child { border-bottom: 0; }
        .muted { color: var(--staff-muted); }
        @media (min-width: 992px) {
            .kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="staff-hero">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h1 class="h4 fw-semibold mb-1 text-white">Admin Staff Dashboard</h1>
                <p class="small mb-0">Focused control panel for requests, stock pressure, and daily operations.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="staff-pill">Today Logs: <?php echo (int)$activity_today; ?></span>
                <span class="staff-pill">Pending: <?php echo (int)$kpis[0]['value']; ?></span>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row g-3">
            <div class="col-12">
                <div class="card section-card">
                    <div class="card-header bg-eclearance text-white fw-semibold">
                        <i class="bi bi-graph-up-arrow"></i>&ensp;Key Metrics
                    </div>
                    <div class="card-body mt-3 bg-white">
                        <?php if (!$has_any_access) { ?>
                            <div class="alert alert-warning mb-3">
                                No module access is assigned to your account yet. Please ask Admin to assign `PO`, `CSM`, or `AST` access.
                            </div>
                        <?php } ?>
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

            <div class="col-12 col-lg-4">
                <div class="signal-card h-100">
                    <h2 class="h6 fw-semibold mb-2">Priority Signals</h2>
                    <div class="signal-row"><span class="muted">Disapproved Requests</span><strong><?php echo (int)$disapproved_reqs; ?></strong></div>
                    <div class="signal-row"><span class="muted">Facility Active</span><strong><?php echo (int)$facility_active; ?></strong></div>
                    <div class="signal-row"><span class="muted">Facility Reported</span><strong><?php echo (int)$facility_reported; ?></strong></div>
                    <?php if ($has_ast) { ?>
                    <div class="signal-row"><span class="muted">AST Unavailable</span><strong><?php echo (int)$ast_unavailable; ?></strong></div>
                    <?php } ?>
                    <?php if ($has_csm) { ?>
                    <div class="signal-row"><span class="muted">CSM Critical Stock</span><strong><?php echo (int)$csm_critical; ?></strong></div>
                    <?php } ?>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card section-card">
                    <div class="card-header fw-semibold">
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
