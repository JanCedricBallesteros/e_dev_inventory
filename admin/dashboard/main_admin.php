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

function dashboard_column_exists($table, $column)
{
    $table = str_replace('`', '``', (string)$table);
    $column = addslashes((string)$column);
    $res = call_mysql_query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $res && mysqli_num_rows($res) > 0;
}

function dashboard_first_existing_column($table, array $candidates, $fallback = '')
{
    foreach ($candidates as $candidate) {
        if (dashboard_column_exists($table, $candidate)) {
            return $candidate;
        }
    }
    return $fallback;
}

$ast_items = dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory");
$ast_categories = dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory_category");
$csm_items = dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory");
$csm_categories = dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory_category");
$pending_reqs = dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='pending'");
$reviewed_reqs = dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='reviewed'");
$approved_reqs = dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='approved'");
$disapproved_reqs = dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE status='disapproved'");
$activity_logs = dashboard_count("SELECT COUNT(*) AS cnt FROM activity_log");
$ast_unavailable = dashboard_count("SELECT COUNT(*) AS cnt FROM ast_inventory WHERE is_available = 0");
$csmQtyColumn = dashboard_first_existing_column('csm_inventory', array('current_quantity', 'current_unit_quantity', 'quantity'));
$csmCritColumn = dashboard_first_existing_column('csm_inventory', array('qty_crit_level', 'unit_crit_level'));
$csm_critical = 0;
if ($csmQtyColumn !== '' && $csmCritColumn !== '') {
    $csm_critical = dashboard_count("SELECT COUNT(*) AS cnt FROM csm_inventory WHERE {$csmQtyColumn} <= {$csmCritColumn}");
}
$facility_active = dashboard_count("SELECT COUNT(*) AS cnt FROM facility_records_assignments WHERE status='ACTIVE'");
$facility_reported = dashboard_count("SELECT COUNT(*) AS cnt FROM facility_records_assignments WHERE status='REPORTED'");
$users_locked = dashboard_count("SELECT COUNT(*) AS cnt FROM users WHERE locked = 1");
$users_inactive = dashboard_count("SELECT COUNT(*) AS cnt FROM users WHERE status = 0");
$activity_today = dashboard_count("SELECT COUNT(*) AS cnt FROM activity_log WHERE DATE(date_log) = CURDATE()");
$latest_activity = "No recent activity found.";
$latest_res = call_mysql_query("SELECT action, date_log FROM activity_log ORDER BY date_log DESC LIMIT 1");
if ($latest_res && ($latest_row = call_mysql_fetch_array($latest_res))) {
    $activity_text = (string)($latest_row['action'] ?? '');
    $activity_text = preg_replace('/::\s*DETAILS::\s*\{.*$/i', '', $activity_text);
    $activity_text = trim($activity_text);
    $latest_activity = date("M d, Y h:i A", strtotime($latest_row['date_log'])) . " - " . $activity_text;
}

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
        :root {
            --dash-ink: #0f172a;
            --dash-muted: #5f6b7a;
            --dash-border: #dce6f2;
            --dash-card: #ffffff;
            --dash-accent: #1f6feb;
            --dash-soft: #ebf3ff;
            --dash-warm: #f59e0b;
            --dash-danger: #e11d48;
        }
        .dashboard-wrap { position: relative; }
        .dash-hero {
            background: #0f4c9f;
            color: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 14px 32px rgba(6, 31, 74, 0.22);
            margin-bottom: 14px;
        }
        .dash-hero h1 { font-weight: 700; }
        .dash-hero p { color: rgba(255,255,255,0.86); }
        .dash-pill {
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.14);
            color: #fff;
            font-size: 12px;
            padding: 4px 10px;
            font-weight: 600;
        }
        .kpi-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; margin-bottom: 14px; }
        .kpi-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 6px 16px rgba(13, 39, 80, 0.06);
        }
        .kpi-label { font-size: 11px; color: var(--dash-muted); text-transform: uppercase; letter-spacing: .05em; }
        .kpi-value { font-size: 24px; font-weight: 700; color: var(--dash-ink); line-height: 1.2; margin-top: 2px; }
        .kpi-sub { font-size: 12px; color: var(--dash-muted); }
        .section-card { border: 1px solid var(--dash-border); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .section-card .card-header { background: #fff; color: var(--dash-ink); border-radius: 12px 12px 0 0; border-bottom: 1px solid var(--dash-border); }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: stretch; }
        .action-card { border: 1px solid var(--dash-border); border-radius: 12px; padding: 12px 14px; background: #fff; display: flex; gap: 12px; align-items: center; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease; }
        .action-card:hover { transform: translateY(-2px); box-shadow: 0 10px 16px rgba(0,0,0,0.08); }
        .action-icon { width: 42px; height: 42px; border-radius: 10px; background: var(--dash-soft); color: var(--dash-accent); display:flex; align-items:center; justify-content:center; font-size: 18px; }
        .action-label { font-size: 12px; color: var(--dash-muted); text-transform: uppercase; letter-spacing: .35px; }
        .action-value { font-size: 20px; font-weight: 700; color: var(--dash-ink); line-height: 1.2; }
        .action-sub { font-size: 12px; color: var(--dash-muted); }
        .signal-card { background: #fff; border: 1px solid var(--dash-border); border-radius: 12px; padding: 12px; }
        .signal-line { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed #e6edf7; }
        .signal-line:last-child { border-bottom: 0; }
        .signal-name { color: var(--dash-muted); font-size: 13px; }
        .signal-value { font-weight: 700; color: var(--dash-ink); }
        .signal-warn { color: #9a6700; }
        .signal-danger { color: var(--dash-danger); }
        .latest-activity-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.35;
            min-height: 2.7em;
        }
        .dashboard-wrap .row.g-3 > [class*="col-"] {
            display: flex;
        }
        .dashboard-wrap .row.g-3 > [class*="col-"] > .card,
        .dashboard-wrap .row.g-3 > [class*="col-"] > .signal-card {
            width: 100%;
            height: 100%;
        }
        .action-card {
            min-height: 84px;
            height: 100%;
        }
        @media (min-width: 992px) {
            .kpi-grid { grid-template-columns: repeat(4, minmax(0,1fr)); }
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="dashboard-wrap">
        <div class="dash-hero">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Admin Dashboard</h1>
                    <p class="small mb-0">Live control center for inventory health, requisitions, and operational load.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="dash-pill">AST: <?php echo (int)$ast_items; ?></span>
                    <span class="dash-pill">CSM: <?php echo (int)$csm_items; ?></span>
                    <span class="dash-pill">Today Logs: <?php echo (int)$activity_today; ?></span>
                </div>
            </div>
        </div>

        <div class="kpi-grid">
            <article class="kpi-card">
                <div class="kpi-label">Pending Requisitions</div>
                <div class="kpi-value"><?php echo (int)$pending_reqs; ?></div>
                <div class="kpi-sub">Needs immediate review</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Critical CSM Stock</div>
                <div class="kpi-value"><?php echo (int)$csm_critical; ?></div>
                <div class="kpi-sub">Current qty at or below critical level</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Unavailable AST Assets</div>
                <div class="kpi-value"><?php echo (int)$ast_unavailable; ?></div>
                <div class="kpi-sub">Marked as not available</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Active Facility Assignments</div>
                <div class="kpi-value"><?php echo (int)$facility_active; ?></div>
                <div class="kpi-sub">Items currently issued</div>
            </article>
        </div>

        <section class="section">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <div class="signal-card h-100">
                        <h2 class="h6 fw-semibold mb-2">Operational Signals</h2>
                        <div class="signal-line">
                            <span class="signal-name">Requisition Reviewed</span>
                            <span class="signal-value"><?php echo (int)$reviewed_reqs; ?></span>
                        </div>
                        <div class="signal-line">
                            <span class="signal-name">Requisition Approved</span>
                            <span class="signal-value"><?php echo (int)$approved_reqs; ?></span>
                        </div>
                        <div class="signal-line">
                            <span class="signal-name">Requisition Disapproved</span>
                            <span class="signal-value signal-warn"><?php echo (int)$disapproved_reqs; ?></span>
                        </div>
                        <div class="signal-line">
                            <span class="signal-name">Facility Reported</span>
                            <span class="signal-value signal-warn"><?php echo (int)$facility_reported; ?></span>
                        </div>
                        <div class="signal-line">
                            <span class="signal-name">Locked Users</span>
                            <span class="signal-value signal-danger"><?php echo (int)$users_locked; ?></span>
                        </div>
                        <div class="signal-line">
                            <span class="signal-name">Inactive Users</span>
                            <span class="signal-value"><?php echo (int)$users_inactive; ?></span>
                        </div>
                        <div class="mt-2">
                            <div class="kpi-label mb-1">Latest Activity</div>
                            <small class="text-muted latest-activity-text"><?php echo html($latest_activity); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8">
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
                <div class="col-12 col-lg-8">
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

                <div class="col-12 col-lg-4">
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
