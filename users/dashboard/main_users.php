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

function user_dashboard_count($sql, $field = 'cnt')
{
    $res = call_mysql_query($sql);
    if ($res && ($row = call_mysql_fetch_array($res))) {
        return (int)($row[$field] ?? 0);
    }
    return 0;
}

$uid = (int)$s_user_id;
$my_pending_reqs = user_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE requester_user_id = {$uid} AND status = 'pending'");
$my_reviewed_reqs = user_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE requester_user_id = {$uid} AND status = 'reviewed'");
$my_approved_reqs = user_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE requester_user_id = {$uid} AND status = 'approved'");
$my_not_claimed_reqs = user_dashboard_count("SELECT COUNT(*) AS cnt FROM requisition_items WHERE requester_user_id = {$uid} AND status = 'not_claimed'");
$my_active_assignments = user_dashboard_count("SELECT COUNT(*) AS cnt FROM facility_records_assignments WHERE issued_to_user_id = {$uid} AND status IN ('ACTIVE','REPORTED','RETURN_REQUESTED')");
$my_returned_assignments = user_dashboard_count("SELECT COUNT(*) AS cnt FROM facility_records_assignments WHERE issued_to_user_id = {$uid} AND status = 'RETURNED'");

$latest_req_text = "No recent requisitions.";
$latest_req_res = call_mysql_query("SELECT module_type, item_code, item_description, status, created_at
                                    FROM requisition_items
                                    WHERE requester_user_id = {$uid}
                                    ORDER BY requisition_id DESC
                                    LIMIT 1");
if ($latest_req_res && ($latest_req = call_mysql_fetch_array($latest_req_res))) {
    $latest_req_text = date("M d, Y h:i A", strtotime($latest_req['created_at'])) . " - " .
        strtoupper((string)($latest_req['module_type'] ?? '')) . " / " .
        (string)($latest_req['item_code'] ?? '') . " (" . strtoupper((string)($latest_req['status'] ?? '')) . ")";
}

$quick_actions = array(
    array(
        'label' => 'Request Items',
        'href' => BASE_URL . 'users/modules/request_items.php',
        'icon' => 'bi-clipboard-plus',
        'sub' => 'Create and track your requests'
    ),
    array(
        'label' => 'Personal Inventory',
        'href' => BASE_URL . 'users/modules/personal_records.php',
        'icon' => 'bi-box-seam',
        'sub' => 'View assigned items and status'
    )
);
if (function_exists('user_has_managing_facility_unit') && user_has_managing_facility_unit()) {
    $quick_actions[] = array(
        'label' => 'Facility Records',
        'href' => BASE_URL . 'users/modules/facility_records.php',
        'icon' => 'bi-building',
        'sub' => 'Manage assigned facility records'
    );
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        :root {
            --usr-ink: #12213b;
            --usr-muted: #5f6e82;
            --usr-border: #d9e5f4;
        }
        .section-card { border: 1px solid var(--usr-border); border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .section-card .card-header { background: #fff; color: var(--usr-ink); border-bottom: 1px solid var(--usr-border); }
        .usr-header {
            border: 1px solid var(--usr-border);
            border-radius: 14px;
            background: #fff;
            padding: 14px 16px;
            margin-bottom: 12px;
        }
        .usr-pill {
            background: #eef5ff;
            color: #144d9f;
            border: 1px solid #cfe0fb;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
        }
        .kpi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
        .kpi-card { background: #fff; border: 1px solid var(--usr-border); border-radius: 11px; padding: 12px; }
        .kpi-label { font-size: 11px; color: var(--usr-muted); text-transform: uppercase; letter-spacing: .05em; }
        .kpi-value { font-size: 22px; font-weight: 700; color: var(--usr-ink); line-height: 1.2; }
        .kpi-sub { font-size: 12px; color: var(--usr-muted); }
        .quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; align-items: stretch; }
        .quick-card {
            border: 1px solid var(--usr-border);
            border-radius: 11px;
            padding: 12px;
            background: #fff;
            display: flex;
            gap: 10px;
            align-items: center;
            text-decoration: none;
            min-height: 84px;
            height: 100%;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .quick-card:hover { transform: translateY(-2px); box-shadow: 0 8px 14px rgba(0,0,0,0.08); }
        .quick-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #eef5ff;
            color: #1f6feb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .signal-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; padding: 7px 0; border-bottom: 1px dashed #e6eef9; }
        .signal-row:last-child { border-bottom: 0; }
        .latest-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.35;
            min-height: 2.7em;
        }
        .muted { color: var(--usr-muted); }
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
        <div class="usr-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h1 class="h4 fw-semibold mb-1">User Dashboard</h1>
                    <p class="text-muted small mb-0" id="greeting-text"></p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="usr-pill"><?php echo htmlspecialchars($display_position); ?></span>
                    <span class="usr-pill">Active Items: <?php echo (int)$my_active_assignments; ?></span>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var name = <?php echo json_encode($display_name); ?>;
            var hour = new Date().getHours();
            var greeting = hour < 12 ? 'Good morning' : (hour < 18 ? 'Good afternoon' : 'Good evening');
            document.getElementById('greeting-text').textContent = greeting + ', ' + name + '.';
        })();
        </script>

        <div class="kpi-grid">
            <article class="kpi-card">
                <div class="kpi-label">Pending Requests</div>
                <div class="kpi-value"><?php echo (int)$my_pending_reqs; ?></div>
                <div class="kpi-sub">Waiting for review</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Reviewed Requests</div>
                <div class="kpi-value"><?php echo (int)$my_reviewed_reqs; ?></div>
                <div class="kpi-sub">Processed by admin</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Approved Requests</div>
                <div class="kpi-value"><?php echo (int)$my_approved_reqs; ?></div>
                <div class="kpi-sub">Ready for claim</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Active Assignments</div>
                <div class="kpi-value"><?php echo (int)$my_active_assignments; ?></div>
                <div class="kpi-sub">Current assigned items</div>
            </article>
        </div>

        <section class="section">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <div class="card section-card">
                        <div class="card-header fw-semibold">
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
                            <hr>
                            <div class="signal-row"><span class="muted">Not Claimed Requests</span><strong><?php echo (int)$my_not_claimed_reqs; ?></strong></div>
                            <div class="signal-row"><span class="muted">Returned Assignments</span><strong><?php echo (int)$my_returned_assignments; ?></strong></div>
                            <div class="mt-2">
                                <div class="small text-muted mb-1">Latest Request</div>
                                <div class="latest-text"><?php echo htmlspecialchars($latest_req_text); ?></div>
                            </div>
                        </div>
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
                                <a class="quick-card" href="<?php echo htmlspecialchars($action['href']); ?>">
                                    <span class="quick-icon"><i class="bi <?php echo htmlspecialchars($action['icon']); ?>"></i></span>
                                    <span>
                                        <span class="d-block fw-semibold text-dark"><?php echo htmlspecialchars($action['label']); ?></span>
                                        <span class="small muted"><?php echo htmlspecialchars($action['sub']); ?></span>
                                    </span>
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




