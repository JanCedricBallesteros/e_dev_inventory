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
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        .superadmin-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .superadmin-toolbar .toolbar-left { flex: 1 1 260px; min-width: 220px; }
        .superadmin-toolbar .toolbar-right { display: flex; flex-wrap: wrap; gap: 8px; }
        .wrap-text { display: block; white-space: normal; word-break: break-word; line-height: 1.25; }
        @media (max-width: 768px) {
            .superadmin-toolbar { flex-direction: column; align-items: stretch; justify-content: flex-start; gap: 8px; }
            .superadmin-toolbar .toolbar-left { flex: 0 0 auto; }
            .superadmin-toolbar .toolbar-right { width: 100%; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .superadmin-toolbar .toolbar-right .btn { width: 100%; }
            .wrap-text { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fas fa-sign-in-alt"></i>&ensp;User Logs
                    </div>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="superadmin-toolbar mb-3">
                        <div class="toolbar-left d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="globalSearch" placeholder="Search logs...">
                            </div>
                            <select id="roleFilter" class="form-select" style="max-width:180px;">
                                <option value="">All Roles</option>
                                <?php
                                if (!empty(SYSTEM_ACCESS['E-INVENTORY']['role'])) {
                                    foreach (SYSTEM_ACCESS['E-INVENTORY']['role'] as $roleKey => $roleName) {
                                        echo '<option value="' . htmlspecialchars($roleName) . '">' . htmlspecialchars($roleName) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="toolbar-right">
                            <button class="btn btn-sm btn-outline-secondary" id="download-csv">Download CSV</button>
                            <button class="btn btn-sm btn-outline-secondary" id="download-json">Download JSON</button>
                            <button class="btn btn-sm btn-outline-primary" id="download-xlsx">Download XLSX</button>
                            <button class="btn btn-sm btn-outline-secondary" id="print-table">Print</button>
                        </div>
                    </div>
                    <div id="user-log-table" class="table table-bordered tabulator"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Cell Detail Modal -->
    <div class="modal fade" id="cellDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cellDetailModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="cellDetailContent" class="mb-0" style="word-break: break-word; white-space: pre-wrap;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once FOOTER_PATH; ?>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
</html>
<script>
(function() {
    var table_data = <?php echo $json_table ? $json_table : '[]'; ?>;
    var total_record = Array.isArray(table_data) ? table_data.length : 0;

    function record_details(values) {
        if (values && values.length) {
            return values.length + ' of ' + total_record;
        }
        return '0 of ' + total_record;
    }

    var cellDetailModalEl = document.getElementById('cellDetailModal');

    const table = new Tabulator("#user-log-table", {
        data: table_data,
        layout: "fitColumns",
        pagination: "local",
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        height: "700px",
        responsiveLayout: "collapse",
        printAsHtml: true,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        movableColumns: true,
        selectable: true,
        selectableRollingSelection: false,
        headerHozAlign: 'center',
        cellVertAlign: "middle",
        printConfig: { columnGroups: false, rowGroups: false },

        columns: [
            { title: "Action", field: "action_display", headerFilter: "input", minWidth: 75, hozAlign: "center" },
            { title: "Date & Time", field: "log_datetime", bottomCalc: record_details, headerFilter: "input", minWidth: 180, hozAlign: "center" },
            { title: "User", field: "name", headerFilter: "input", minWidth: 200 },
            { title: "Role", field: "role_label", headerFilter: "input", minWidth: 160 },
            { title: "Username", field: "username", headerFilter: "input", minWidth: 160 },
            { 
                title: "Email", 
                field: "email_address", 
                headerFilter: "input", 
                minWidth: 200,
                formatter: function(cell) {
                    const v = cell.getValue() || '';
                    if (!v || v.trim() === '') return '-';
                    const span = document.createElement('span');
                    span.className = 'wrap-text';
                    span.textContent = v;
                    span.title = v;
                    return span;
                }
            },
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
                    span.className = 'device-truncated';
                    span.style.cursor = 'pointer';
                    span.style.color = '#0d6efd';
                    span.style.textDecoration = 'underline';
                    span.title = 'Click to view full device info';
                    span.textContent = truncated;
                    span.onclick = function(e) {
                        e.stopPropagation();
                        document.getElementById('cellDetailModalTitle').innerHTML = '<i class="bi bi-phone"></i>&ensp;Device Information';
                        document.getElementById('cellDetailContent').textContent = value;
                        $('#cellDetailModal').modal('show');
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
    document.getElementById('print-table').addEventListener('click', function() {
        table.print(false, true);
    });

    document.getElementById('roleFilter').addEventListener('change', function() {
        const val = this.value.trim();
        if (!val) {
            table.clearFilter(true);
            return;
        }
        table.setFilter("role_label", "like", val);
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
                data.role_label, data.position, data.ip_address, data.device
            ].join(' ').toLowerCase();
            return hay.indexOf(q) !== -1;
        });
    });
})();
</script>
