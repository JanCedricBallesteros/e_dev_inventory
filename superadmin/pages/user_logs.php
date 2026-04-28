<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!role_has("SUPER_ADMIN")) {
    header("Location: " . BASE_URL);
    exit();
}

$table_array = array();

$has_events = false;
$check = call_mysql_query("SHOW TABLES LIKE 'user_log_events'");
if ($check && mysqli_num_rows($check) > 0) {
    $has_events = true;
}

// Pick the fresher log source:
// - If user_log has newer entries than user_log_events, use legacy source.
// - Otherwise use user_log_events.
$use_events_source = $has_events;
if ($has_events) {
    $maxEventTime = '';
    $maxLegacyTime = '';

    $qEvent = call_mysql_query("SELECT MAX(event_time) AS max_event_time FROM user_log_events");
    if ($qEvent && ($rEvent = call_mysql_fetch_array($qEvent))) {
        $maxEventTime = trim((string)($rEvent['max_event_time'] ?? ''));
    }

    $qLegacy = call_mysql_query("
        SELECT
            GREATEST(
                COALESCE(MAX(login_date), '0000-00-00 00:00:00'),
                COALESCE(MAX(logout_date), '0000-00-00 00:00:00')
            ) AS max_legacy_time
        FROM user_log
    ");
    if ($qLegacy && ($rLegacy = call_mysql_fetch_array($qLegacy))) {
        $maxLegacyTime = trim((string)($rLegacy['max_legacy_time'] ?? ''));
    }

    if (_is_valid_log_date($maxLegacyTime) && (!_is_valid_log_date($maxEventTime) || $maxLegacyTime > $maxEventTime)) {
        $use_events_source = false;
    }
}

if ($use_events_source) {
    $select = "
        SELECT
            e.event_id,
            e.user_id,
            e.action,
            e.event_time,
            e.ip_address,
            e.device,
            e.user_level,
            u.general_id,
            u.f_name,
            u.m_name,
            u.l_name,
            u.suffix,
            u.user_role AS roles,
            u.username,
            u.email_address,
            u.position
        FROM user_log_events e
        LEFT JOIN users u ON u.user_id = e.user_id
        ORDER BY e.event_time DESC, e.event_id DESC
    ";
} else {
    $select = "
        SELECT
            ul.user_log_id,
            ul.login_date,
            ul.logout_date,
            ul.action,
            ul.user_id,
            ul.session_id,
            ul.ip_address,
            ul.device,
            ul.login_flag,
            ul.user_level,
            u.general_id,
            u.f_name,
            u.m_name,
            u.l_name,
            u.suffix,
            u.user_role AS roles,
            u.username,
            u.email_address,
            u.position
        FROM user_log ul
        LEFT JOIN users u ON u.user_id = ul.user_id
        ORDER BY ul.user_log_id DESC
    ";
}

function _is_valid_log_date($d) {
    if ($d === null) return false;
    $d = trim((string)$d);
    if ($d === '' || $d === '0000-00-00 00:00:00' || strtoupper($d) === 'NULL') return false;
    return true;
}

if ($query = call_mysql_query($select)) {
    if (mysqli_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data['name'] = get_full_name(
                $data['f_name'] ?? '',
                $data['m_name'] ?? '',
                $data['l_name'] ?? '',
                $data['suffix'] ?? ''
            );

            // Role label: prefer event role if available, otherwise from user roles
            $data['role_label'] = '';
            if (isset($data['user_level']) && $data['user_level'] !== '') {
                $lvl = (string)$data['user_level'];
                if (isset(SYSTEM_ACCESS['E-INVENTORY']['role'][$lvl])) {
                    $data['role_label'] = SYSTEM_ACCESS['E-INVENTORY']['role'][$lvl];
                } else {
                    $data['role_label'] = $lvl;
                }
            } elseif (!empty($data['roles'])) {
                $roles = json_decode($data['roles'], true);
                if (is_array($roles)) {
                    $mapped = [];
                    foreach ($roles as $role) {
                        if (isset(SYSTEM_ACCESS['E-INVENTORY']['role'][$role])) {
                            $mapped[] = SYSTEM_ACCESS['E-INVENTORY']['role'][$role];
                        }
                    }
                    $data['role_label'] = implode(', ', $mapped);
                }
            }

            if ($use_events_source) {
                $row = $data;
                $row['action_display'] = strtoupper(trim((string)($data['action'] ?? '')));
                $row['log_datetime'] = $data['event_time'] ?? '';
                $table_array[] = $row;
            } else {
                $emitted = false;
                $events = json_decode((string)($data['session_id'] ?? ''), true);
                if (is_array($events)) {
                    foreach ($events as $evt) {
                        if (!is_array($evt)) continue;
                        $evtDate = $evt[0] ?? '';
                        $evtAction = strtoupper(trim((string)($evt[1] ?? '')));
                        $evtIp = $evt[2] ?? '';
                        if (!_is_valid_log_date($evtDate)) continue;
                        $row = $data;
                        $row['action_display'] = $evtAction !== '' ? $evtAction : trim((string)($data['action'] ?? ''));
                        $row['log_datetime'] = $evtDate;
                        if ($evtIp !== '') {
                            $row['ip_address'] = $evtIp;
                        }
                        $table_array[] = $row;
                        $emitted = true;
                    }
                }

                if (!$emitted) {
                    $loginDate = $data['login_date'] ?? '';
                    $logoutDate = $data['logout_date'] ?? '';
                    $hasLogin = _is_valid_log_date($loginDate);
                    $hasLogout = _is_valid_log_date($logoutDate);

                    if ($hasLogin) {
                        $row = $data;
                        $row['action_display'] = 'LOGIN';
                        $row['log_datetime'] = $loginDate;
                        $table_array[] = $row;
                    }
                    if ($hasLogout) {
                        $row = $data;
                        $row['action_display'] = 'LOGOUT';
                        $row['log_datetime'] = $logoutDate;
                        $table_array[] = $row;
                    }
                    if (!$hasLogin && !$hasLogout) {
                        $row = $data;
                        $row['action_display'] = trim((string)($data['action'] ?? ''));
                        $row['log_datetime'] = '';
                        $table_array[] = $row;
                    }
                }
            }
        }
    }
}

$json_table = output($table_array);
$total_records = count($table_array);
$unique_users = 0;
$unique_actions = 0;
$user_seen = array();
$action_seen = array();
foreach ($table_array as $row) {
    $uid = trim((string)($row['user_id'] ?? ''));
    if ($uid !== '') {
        $user_seen[$uid] = true;
    }
    $act = trim((string)($row['action_display'] ?? ''));
    if ($act !== '') {
        $action_seen[strtoupper($act)] = true;
    }
}
$unique_users = count($user_seen);
$unique_actions = count($action_seen);
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --dash-ink: #1f2937;
            --dash-muted: #6c757d;
            --dash-border: #e5e7eb;
            --dash-card: #ffffff;
            --dash-accent: #0d6efd;
            --dash-accent-soft: #eef5ff;
        }
        .section-card { border: 1px solid var(--dash-border); border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .section-card .card-header{ background: var(--bg-eclearance-rgb); color: #fff; border-radius: 12px 12px 0 0; font-weight: 600; }
        .toolbar-grid { display: grid; grid-template-columns: minmax(240px, 1fr) auto; gap: 10px; align-items: end; }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        .tabulator { font-size: 0.875rem; }
        .tabulator .tabulator-cell { vertical-align: middle; }
        .tabulator .tabulator-row:hover { background: #f8fbff; }
        .wrap-text { display: block; white-space: normal; word-break: break-word; line-height: 1.25; }
        @media (max-width: 992px) {
            .toolbar-grid { grid-template-columns: 1fr; }
            .toolbar-actions { justify-content: flex-start; }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 class="h4 fw-semibold mb-1">User Logs</h1>
            <p class="text-muted small mb-0">Track sign-in and sign-out activity with the same current admin layout language.</p>
        </div>

        <section class="section">
            <div class="card section-card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-clock-history"></i>&ensp;User Log Activity</span>
                    <button type="button" class="btn btn-light btn-sm" id="print-table"><i class="bi bi-printer"></i> Print</button>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="toolbar-grid mb-3">
                        <div>

                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="globalSearch" class="form-control" placeholder="Search user logs...">
                            </div>
                        </div>
                        <div class="toolbar-actions">
                            <button class="btn btn-outline-secondary" id="download-csv" type="button">CSV</button>
                            <button class="btn btn-outline-secondary" id="download-json" type="button">JSON</button>
                            <button class="btn btn-outline-secondary" id="download-xlsx" type="button">XLSX</button>
                            <button class="btn btn-outline-primary" id="print-table-btn" type="button">Print</button>
                        </div>
                    </div>

                    <div id="user-log-table" class="tabulator"></div>
                </div>
            </div>
        </section>
    </main>

    <div class="modal fade" id="deviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="deviceModalTitle">Device Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deviceModalContent" class="mb-0" style="word-break: break-word; white-space: pre-wrap;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once FOOTER_PATH; ?>

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
    <script>
(function() {
    var tableData = <?php echo $json_table ? $json_table : '[]'; ?>;
    var totalRecords = Array.isArray(tableData) ? tableData.length : 0;

    const table = new Tabulator("#user-log-table", {
        data: tableData,
        layout: "fitColumns",
        pagination: "local",
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        paginationCounter: "rows",
        placeholder: "No user logs found",
        movableColumns: true,
        responsiveLayout: "collapse",
        cellVertAlign: "middle",
        printAsHtml: true,
        columns: [
            { title: "Action", field: "action_display", headerFilter: "input", minWidth: 75, hozAlign: "center" },
            { title: "Date & Time", field: "log_datetime", headerFilter: "input", minWidth: 180, hozAlign: "center" },
            { title: "User", field: "name", headerFilter: "input", minWidth: 200 },
            { title: "Role", field: "role_label", headerFilter: "input", minWidth: 160 },
            { title: "Username", field: "username", headerFilter: "input", minWidth: 160 },
            { title: "Email", field: "email_address", headerFilter: "input", minWidth: 200 },
            { title: "Position", field: "position", headerFilter: "input", minWidth: 180 },
            { title: "IP", field: "ip_address", headerFilter: "input", minWidth: 120 },
            {
                title: "Device",
                field: "device",
                headerFilter: "input",
                minWidth: 220,
                formatter: function(cell) {
                    const value = cell.getValue();
                    if (!value || value.trim() === '') return '-';
                    const maxLength = 40;
                    if (value.length <= maxLength) return value;
                    const truncated = value.substring(0, maxLength) + '...';
                    const span = document.createElement('span');
                    span.style.cursor = 'pointer';
                    span.style.color = '#0d6efd';
                    span.style.textDecoration = 'underline';
                    span.title = 'Click to view full device info';
                    span.textContent = truncated;
                    span.onclick = function(e) {
                        e.stopPropagation();
                        document.getElementById('deviceModalContent').textContent = value;
                        $('#deviceModal').modal('show');
                    };
                    return span;
                }
            }
        ]
    });

    document.getElementById('download-csv').addEventListener('click', function() {
        table.download("csv", "user_logs.csv");
    });
    document.getElementById('download-json').addEventListener('click', function() {
        table.download("json", "user_logs.json");
    });
    document.getElementById('download-xlsx').addEventListener('click', function() {
        table.download("xlsx", "user_logs.xlsx", { sheetName: "User Logs" });
    });
    document.getElementById('print-table-btn').addEventListener('click', function() {
        table.print(false, true);
    });

    document.getElementById('globalSearch').addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        if (!q) {
            table.clearFilter(true);
            return;
        }
        table.setFilter(function(data) {
            const hay = [
                data.name, data.username, data.email_address,
                data.role_label, data.position, data.ip_address, data.device, data.action_display, data.log_datetime
            ].join(' ').toLowerCase();
            return hay.indexOf(q) !== -1;
        });
    });
})();
    </script>

</body>
</html>
